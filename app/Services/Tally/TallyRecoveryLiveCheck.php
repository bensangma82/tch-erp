<?php

namespace App\Services\Tally;

use App\Models\TallyExport;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use SimpleXMLElement;

class TallyRecoveryLiveCheck
{
    public function assertAbsent(TallyExport $export): string
    {
        $company = trim((string) config('tally.company'));
        if (! str_contains(strtolower($company), '-test')) {
            throw new RuntimeException('Simple recovery is restricted to the configured test company.');
        }
        $client = app(TallyHttpClient::class);
        $companies = $this->collection($client->postXml($this->request($company, 'Recovery Companies', 'Company', 'NAME')));
        $found = false;
        foreach ($companies->xpath('.//COMPANY') ?: [] as $node) {
            if (trim((string) ($node['NAME'] ?? $node->NAME)) === $company) $found = true;
        }
        if (! $found) throw new RuntimeException('Configured company is not loaded in live Tally. No retry authorized.');
        $raw = $client->postXml($this->request($company, 'Recovery Vouchers', 'Voucher', 'DATE,VOUCHERTYPENAME,VOUCHERNUMBER,NARRATION,REFERENCE,REMOTEID,GUID,MASTERID'));
        $collection = $this->collection($raw);
        $export->loadMissing('journalEntry', 'journalExportAttempts');
        $identities = array_filter(array_merge(
            [(string) $export->remote_id, (string) $export->export_reference,
                (string) $export->journalEntry?->journal_no, (string) $export->journalEntry?->reference_no],
            $export->journalExportAttempts->pluck('remote_id')->all(),
            $export->journalExportAttempts->pluck('export_reference')->all()
        ));
        if (! $export->journalEntry || ! $export->remote_id) throw new RuntimeException('Original export identity is missing.');
        foreach ($collection->xpath('.//VOUCHER') ?: [] as $node) {
            $text = (string) $node->asXML();
            foreach ($identities as $identity) {
                // Any original/recovery identity is a candidate. No amount-only auto-confirmation.
                if (preg_match('/(?<![A-Za-z0-9_-])'.preg_quote((string) $identity, '/').'(?![A-Za-z0-9_-])/i', html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8'))) {
                    throw ValidationException::withMessages(['recovery' => 'A possible existing voucher was found in live Tally (voucher '.(string) $node->VOUCHERNUMBER.'). Nothing resent. Reconcile the existing voucher.']);
                }
            }
        }
        return '[AUTO-LIVE] Day Book identity check; company '.$company
            .'; journal '.$export->journalEntry->journal_no.'; export #'.$export->id
            .'; checked at '.now()->toIso8601String().'; user '.auth()->id()
            .'; complete voucher collection requested for 1900-2099; no export, journal or invoice identity found'
            .'; response SHA256 '.hash('sha256', $raw).'.';
    }

    private function request(string $company, string $name, string $type, string $fetch): string
    {
        $company = htmlspecialchars($company, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        return '<?xml version="1.0"?><ENVELOPE><HEADER><VERSION>1</VERSION><TALLYREQUEST>Export</TALLYREQUEST><TYPE>Collection</TYPE><ID>'.$name.'</ID></HEADER>'
            .'<BODY><DESC><STATICVARIABLES><SVCURRENTCOMPANY>'.$company.'</SVCURRENTCOMPANY><SVEXPORTFORMAT>$$SysName:XML</SVEXPORTFORMAT>'
            .'<SVFROMDATE TYPE="Date">19000101</SVFROMDATE><SVTODATE TYPE="Date">20991231</SVTODATE></STATICVARIABLES>'
            .'<TDL><TDLMESSAGE><COLLECTION NAME="'.$name.'"><TYPE>'.$type.'</TYPE><FETCH>'.$fetch.'</FETCH></COLLECTION></TDLMESSAGE></TDL></DESC></BODY></ENVELOPE>';
    }

    private function collection(string $raw): SimpleXMLElement
    {
        if (preg_match('/<!DOCTYPE|<!ENTITY/i', $raw)) throw new RuntimeException('Unexpected Tally XML declarations.');
        $previous = libxml_use_internal_errors(true);
        try { $xml = simplexml_load_string($raw, SimpleXMLElement::class, LIBXML_NONET); }
        finally { libxml_clear_errors(); libxml_use_internal_errors($previous); }
        if (! $xml || ($xml->xpath('//LINEERROR|//ERROR|//ERRORS') ?: [])) throw new RuntimeException('Tally query failed; no retry authorized.');
        $collections = $xml->xpath('/ENVELOPE/BODY/DATA/COLLECTION') ?: [];
        if (count($collections) !== 1) throw new RuntimeException('Incomplete or unexpected Tally collection response; no retry authorized.');
        return $collections[0];
    }
}
