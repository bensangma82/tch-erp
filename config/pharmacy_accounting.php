<?php

return [
    /*
     * Default conservatively for a hospital with exempt/mixed healthcare
     * supplies: GST on Pharmacy purchases is included in inventory cost unless
     * the accountant confirms input tax credit eligibility and enables this.
     */
    'input_gst_recoverable' => (bool) env('PHARMACY_INPUT_GST_RECOVERABLE', false),
];
