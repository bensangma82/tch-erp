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
        if (! $export->journalEntry || ! $export->remote_id) throw new RuntimeException('Original export identity is missing.');
        $journal = $export->journalEntry;
        $billReference = trim((string) $journal->reference_no);
        $identities = array_filter(array_merge(
            [(string) $export->remote_id, (string) $export->export_reference, (string) $journal->journal_no],
            $export->journalExportAttempts->pluck('remote_id')->all(),
            $export->journalExportAttempts->pluck('export_reference')->all()
        ));
        $isIpdSales = $journal->source_type === 'ip_billing_account' && $journal->source_event === 'finalized';
        $isInvoiceSales = $journal->source_type === 'invoice'
            && $journal->source_event === 'issued'
            && str_starts_with(strtoupper($billReference), 'INV-');
        $isMhisEntitlement = $journal->source_type === 'ip_billing_mhis_claim'
            && $journal->source_event === 'approved'
            && str_starts_with(strtoupper($billReference), 'IPF-');
        $relatedReceipts = 0;
        $relatedMhisVouchers = 0;

        foreach ($collection->xpath('.//VOUCHER') ?: [] as $node) {
            $type = trim((string) $node->VOUCHERTYPENAME);
            $text = html_entity_decode((string) $node->asXML(), ENT_QUOTES | ENT_XML1, 'UTF-8');
            foreach ($identities as $identity) {
                if ($this->containsIdentity($text, (string) $identity)) {
                    $this->blockForMatch($node, $type);
                }
            }
            if ($billReference === '' || ! $this->containsIdentity($text, $billReference)) {
                continue;
            }
            // A Receipt can refer to the bill or invoice without being the Sales voucher.
            if (($isIpdSales || $isInvoiceSales) && strcasecmp($type, 'Receipt') === 0) {
                $relatedReceipts++;
                continue;
            }
            // The original sale and patient receipt can both reference the IPD bill.
            if ($isMhisEntitlement && in_array(strtolower($type), ['receipt', 'sales'], true)) {
                $relatedMhisVouchers++;
                continue;
            }
            $this->blockForMatch($node, $type);
        }

        return '[AUTO-LIVE] Day Book identity check; company '.$company
            .'; journal '.$export->journalEntry->journal_no.'; export #'.$export->id
            .'; checked at '.now()->toIso8601String().'; user '.auth()->id()
            .'; complete voucher collection requested for 1900-2099; no matching export, journal or original voucher bill identity found'
            .($relatedReceipts ? '; bill-only references in Receipts excluded: '.$relatedReceipts : '')
            .($relatedMhisVouchers ? '; bill-only references in Sales or Receipts excluded: '.$relatedMhisVouchers : '')
            .'; response SHA256 '.hash('sha256', $raw).'.';
    }

    private function containsIdentity(string $text, string $identity): bool
    {
        return preg_match('/(?<![A-Za-z0-9_-])'.preg_quote($identity, '/').'(?![A-Za-z0-9_-])/i', $text) === 1;
    }

    private function blockForMatch(SimpleXMLElement $node, string $type): never
    {
        $details = implode(' ', array_filter([$type, 'voucher '.(string) $node->VOUCHERNUMBER,
            (string) $node->DATE]));
        throw ValidationException::withMessages([
            'recovery' => 'A possible existing voucher was found in live Tally ('.$details
                .'). Nothing resent. Reconcile the existing voucher.',
        ]);
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
