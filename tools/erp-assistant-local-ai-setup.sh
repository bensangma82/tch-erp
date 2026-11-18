#!/usr/bin/env bash
set -euo pipefail
# Optional Ubuntu-only model setup. The ERP patch itself never installs software or changes .env.
if [[ "$(uname -s)" != Linux ]] || [[ ! -f artisan ]] || [[ ! -f config/erp_assistant.php ]]; then
    echo 'Run from the Ubuntu tch-erp project root after installing ERP Assistant.'; exit 1
fi
for dependency in curl python3 php; do command -v "$dependency" >/dev/null || { echo "Required command unavailable: $dependency"; exit 1; }; done
TCH_AI_AVAILABLE_KB=$(awk '/^MemAvailable:/ {print $2}' /proc/meminfo)
if [[ "$TCH_AI_AVAILABLE_KB" -ge 4194304 ]]; then
    TCH_AI_SETUP_MODEL='qwen2.5:1.5b'
elif [[ "$TCH_AI_AVAILABLE_KB" -ge 2097152 ]]; then
    TCH_AI_SETUP_MODEL='qwen2.5:0.5b'
else
    echo 'Less than 2 GiB memory is currently available. Model installation was not attempted. Keep guided mode or provide more server memory.'; exit 1
fi
export TCH_AI_SETUP_MODEL
TCH_AI_DISK_KB=$(df -Pk . | awk 'NR==2 {print $4}')
if [[ "$TCH_AI_DISK_KB" -lt 6291456 ]]; then echo 'At least 6 GiB free disk space is required by this setup guard. Nothing changed.'; exit 1; fi
printf 'Available memory: %s MiB. Selected local model: %s\n' "$((TCH_AI_AVAILABLE_KB/1024))" "$TCH_AI_SETUP_MODEL"
TCH_AI_INSTALLED=0
if ! command -v ollama >/dev/null; then
    TCH_AI_INSTALL_SCRIPT=$(mktemp /tmp/tch-ollama-install-XXXXXXXX.sh)
    trap 'rm -f "$TCH_AI_INSTALL_SCRIPT"' EXIT
    curl --proto '=https' --proto-redir '=https' --tlsv1.2 -fsSL https://ollama.com/install.sh -o "$TCH_AI_INSTALL_SCRIPT"
    sh "$TCH_AI_INSTALL_SCRIPT"
    TCH_AI_INSTALLED=1
fi
if ! curl -fsS --connect-timeout 2 --max-time 5 http://127.0.0.1:11434/api/tags >/dev/null; then
    sudo systemctl start ollama
fi
curl -fsS --connect-timeout 2 --max-time 5 http://127.0.0.1:11434/api/tags >/dev/null
if command -v ss >/dev/null; then
    while IFS= read -r TCH_AI_LISTENER; do
        case "$TCH_AI_LISTENER" in 127.0.0.1:11434|'[::1]:11434'|::1:11434) ;; *) if [[ "$TCH_AI_INSTALLED" == 1 ]]; then sudo systemctl stop ollama; fi; echo 'Ollama is listening beyond loopback. Bind it to localhost before enabling this local assistant.'; exit 1 ;; esac
    done < <(ss -ltnH 'sport = :11434' | awk '{print $4}')
else
    echo 'Cannot verify the local listener: ss is unavailable. Configure localhost-only Ollama access before enabling.'; exit 1
fi
OLLAMA_HOST=http://127.0.0.1:11434 ollama pull "$TCH_AI_SETUP_MODEL"
# Warm and verify structured intent classification using a synthetic question, with no ERP records.
php artisan tinker --execute='config(["erp_assistant.local_ai_enabled" => true, "erp_assistant.local_ai_url" => "http://127.0.0.1:11434", "erp_assistant.local_ai_model" => getenv("TCH_AI_SETUP_MODEL"), "erp_assistant.timeout" => 20]); $result = app(\App\Services\Assistant\LocalIntentRouter::class)->interpret("What work is pending?"); if (($result["mode"] ?? null) !== "local_ai" || ($result["intent"] ?? null) !== "summary" || ($result["category"] ?? null) !== "all") { fwrite(STDERR, "Expected local_ai / summary / all, received ".json_encode($result).PHP_EOL); exit(1); } dump("Local model intent check passed.");'
python3 - <<'PY'
from pathlib import Path
import os,re,stat,tempfile,datetime
path=Path('.env')
if path.is_symlink() or not path.is_file():raise SystemExit('A regular project .env file is required. Configure AI settings manually.')
model=os.environ['TCH_AI_SETUP_MODEL']
if model not in ['qwen2.5:1.5b','qwen2.5:0.5b']:raise SystemExit('Unexpected model; environment unchanged.')
old=path.read_bytes();text=old.decode();values={'ERP_ASSISTANT_LOCAL_AI_ENABLED':'true','ERP_ASSISTANT_LOCAL_AI_URL':'http://127.0.0.1:11434','ERP_ASSISTANT_LOCAL_AI_MODEL':model}
for key,value in values.items():
    pattern=r'^'+re.escape(key)+r'=[^\r\n]*'
    matches=list(re.finditer(pattern,text,re.M))
    if len(matches)>1:raise SystemExit('Duplicate AI environment keys found. Resolve them manually; nothing changed.')
    if matches:text=re.sub(pattern,lambda _:key+'='+value,text,flags=re.M)
    else:text=text.rstrip('\r\n')+'\n'+key+'='+value+'\n'
backup=Path('storage/app/patch-backups')/('erp-assistant-ai-'+datetime.datetime.now().strftime('%Y%m%d-%H%M%S-%f'))
backup.mkdir(parents=True,mode=0o700);os.chmod(backup,0o700)
(backup/'.env').write_bytes(old);os.chmod(backup/'.env',0o600)
info=path.stat();fd,pending=tempfile.mkstemp(prefix='.erp-assistant-env-',dir='.')
try:
    with os.fdopen(fd,'wb') as out:out.write(text.encode());out.flush();os.fsync(out.fileno())
    os.chown(pending,info.st_uid,info.st_gid);os.chmod(pending,stat.S_IMODE(info.st_mode));os.replace(pending,path)
finally:
    if os.path.exists(pending):os.unlink(pending)
print('Saved the three local-AI settings. Other environment values are retained. Private environment backup: '+str(backup))
PY
php artisan config:clear
php artisan tinker --execute='if (!config("erp_assistant.local_ai_enabled", false)) { throw new \RuntimeException("AI settings did not become active. Check configuration before retrying."); } dump("Local AI configuration is active.");'
printf 'Local AI is enabled with %s. Refresh ERP Assistant and ask about pending work. No ERP transaction was performed.\n' "$TCH_AI_SETUP_MODEL"
