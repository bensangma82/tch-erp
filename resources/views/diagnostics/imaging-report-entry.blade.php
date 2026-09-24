<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Imaging Report Entry</h2>
            <p class="mt-1 text-sm text-gray-500">
                Enter findings and impression for the imaging study.
            </p>
        </div>
    </x-slot>

    @php
        $order = $serviceOrderItem->serviceOrder;
        $patient = $order?->patient;
        $encounter = $order?->encounter;
        $existingResult = $serviceOrderItem->diagnosticResult;

        $studyText = strtolower(
            trim(
                ($serviceOrderItem->service_name ?? '') . ' ' .
                ($serviceOrderItem->service_code ?? '')
            )
        );

        $isAbdominalUsg =
            str_contains($studyText, 'ultrasound abdomen')
            || str_contains($studyText, 'ultrasound whole abdomen')
            || str_contains($studyText, 'usg abdomen')
            || str_contains($studyText, 'usg whole abdomen')
            || str_contains($studyText, 'usgabd');

        $isEchocardiography =
            str_contains($studyText, 'echocardiography')
            || str_contains($studyText, '2d echo')
            || str_contains($studyText, '2-d echo')
            || str_contains($studyText, 'echo cardiography');

        $echoData = old(
            'structured_data',
            is_array($existingResult?->structured_data)
                ? $existingResult->structured_data
                : []
        );

        $echoValue = function (string $path, $default = '') use ($echoData) {
            return data_get($echoData, $path, $default);
        };
    @endphp

    <div class="py-6">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3">
                    <ul class="list-inside list-disc text-sm text-red-700">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

                {{-- PATIENT / STUDY INFO --}}
                <div class="border-b border-gray-200 bg-gray-50 px-6 py-5">
                    <div class="grid gap-6 sm:grid-cols-2">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Patient</div>
                            <div class="mt-1 text-lg font-semibold text-gray-900">
                                {{ $patient?->full_name ?? '—' }}
                            </div>
                            <div class="mt-2 text-sm text-gray-500">UHID: {{ $patient?->uhid ?? '—' }}</div>
                            <div class="text-sm text-gray-500">MRD: {{ $patient?->mrd_number ?: '—' }}</div>
                            <div class="text-sm text-gray-500">
                                @if ($patient?->age !== null)
                                    {{ $patient->age }} yrs
                                @else
                                    Age —
                                @endif
                                / {{ $patient?->sex ?: '—' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Imaging Study</div>
                            <div class="mt-1 text-lg font-semibold text-gray-900">
                                {{ $serviceOrderItem->service_name }}
                            </div>
                            <div class="mt-2 text-sm text-gray-500">Code: {{ $serviceOrderItem->service_code }}</div>
                            <div class="text-sm text-gray-500">Order: {{ $order?->order_no ?? '—' }}</div>
                            <div class="text-sm text-gray-500">
                                Department: {{ $encounter?->department?->name ?? '—' }}
                            </div>
                            <div class="text-sm text-gray-500">
                                Doctor: {{ $encounter?->doctor?->full_name ?? 'Unassigned' }}
                            </div>
                        </div>
                    </div>
                </div>

                @if ($existingResult?->status === 'draft')
                    <div class="border-b border-blue-200 bg-blue-50 px-6 py-4">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <div class="text-sm font-semibold text-blue-800">Draft Report</div>
                                <p class="mt-1 text-sm text-blue-700">
                                    This report has been saved as a draft and may still be edited.
                                </p>
                            </div>
                            @if ($existingResult?->entered_at)
                                <div class="text-xs text-blue-700">
                                    Last saved: {{ $existingResult->entered_at->format('d M Y, h:i A') }}
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <form
                    method="POST"
                    action="{{ route('diagnostics.items.imaging-report.save', $serviceOrderItem) }}"
                    class="p-6"
                    id="imaging-report-form"
                >
                    @csrf

                    @if ($isEchocardiography)
                        {{-- STRUCTURED 2D ECHOCARDIOGRAPHY REPORTING --}}
                        <input type="hidden" name="structured_data[modality]" value="echocardiography">

                        <div id="echo-builder" class="mb-7 rounded-xl border border-sky-200 bg-sky-50/40">
                            <div class="border-b border-sky-200 px-5 py-4">
                                <div>
                                    <h3 class="text-base font-semibold text-sky-900">Structured 2D Echocardiography Reporting</h3>
                                    <p class="mt-1 text-sm text-sky-700">
                                        Enter measurements, Doppler values, 2D findings and valve assessment.
                                        Calculated fields and the source tool's auto-interpretation rules are retained.
                                    </p>
                                    <div class="mt-4">
                                        <button type="button" id="generate-echo-report"
                                                style="display:block !important; visibility:visible !important; opacity:1 !important; margin-top:16px; padding:12px 20px; background:#0369a1; color:white; border:0; border-radius:8px; font-weight:600; cursor:pointer;">
                                            Generate Findings &amp; Impression
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-7 p-5">
                                {{-- OPTIONAL CLINICAL DATA --}}
                                <section>
                                    <h4 class="text-sm font-semibold uppercase tracking-wide text-gray-700">Clinical Data</h4>
                                    <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                        @foreach ([
                                            ['clinical.bp', 'Blood Pressure', 'e_bp', 'e.g. 120/80'],
                                            ['clinical.hr', 'Heart Rate', 'e_hr', 'bpm'],
                                            ['clinical.height', 'Height', 'e_height', 'cm'],
                                            ['clinical.weight', 'Weight', 'e_weight', 'kg'],
                                        ] as [$path, $label, $id, $placeholder])
                                            <label class="block">
                                                <span class="text-xs font-semibold text-gray-600">{{ $label }}</span>
                                                <input id="{{ $id }}"
                                                       name="structured_data[{{ str_replace('.', '][', $path) }}]"
                                                       value="{{ $echoValue($path) }}"
                                                       placeholder="{{ $placeholder }}"
                                                       class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm">
                                            </label>
                                        @endforeach
                                    </div>
                                    <label class="mt-3 block">
                                        <span class="text-xs font-semibold text-gray-600">BSA (calculated)</span>
                                        <input id="e_bsa" name="structured_data[clinical][bsa]"
                                               value="{{ $echoValue('clinical.bsa') }}" readonly
                                               class="mt-1 block w-full rounded-lg border-gray-300 bg-gray-100 text-sm shadow-sm sm:max-w-xs">
                                    </label>
                                    <label class="mt-3 block">
                                        <span class="text-xs font-semibold text-gray-600">Clinical Indication</span>
                                        <textarea id="e_indication" name="structured_data[clinical][indication]" rows="2"
                                                  class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm">{{ $echoValue('clinical.indication') }}</textarea>
                                    </label>
                                </section>

                                {{-- M-MODE --}}
                                <section>
                                    <h4 class="text-sm font-semibold uppercase tracking-wide text-gray-700">M-Mode Measurements</h4>
                                    <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                        @foreach ([
                                            ['m_aod','Aortic Root','mm','20-37',20,37,false],
                                            ['m_la','Left Atrium','mm','27-40',27,40,false],
                                            ['m_la_ao','LA/Ao Ratio','','<1.3',null,1.3,true],
                                            ['m_lvidd','LVID Diastole','mm','35-55',35,55,false],
                                            ['m_lvids','LVID Systole','mm','25-35',25,35,false],
                                            ['m_ivsd','IVS Thickness','mm','6-11',6,11,false],
                                            ['m_pwd','Post. Wall Thick.','mm','6-11',6,11,false],
                                            ['m_ef','Ejection Fraction','%','55-70',50,null,false],
                                            ['m_fs','Fract. Shortening','%','25-45',25,null,true],
                                            ['m_rv','RV Dimension','mm','15-30',15,30,false],
                                            ['m_tapse','TAPSE','mm','>17',17,null,false],
                                            ['m_ivc','IVC Diameter','cm','<2.1',null,2.1,false],
                                            ['m_rap','Est. RA Pressure','mmHg','3-15',null,15,false],
                                        ] as [$id,$label,$unit,$range,$min,$max,$auto])
                                            <label class="block">
                                                <span class="text-xs font-semibold text-gray-600">{{ $label }}</span>
                                                <div class="mt-1 flex">
                                                    <input id="{{ $id }}" type="number" step="any"
                                                           name="structured_data[m_mode][{{ $id }}]"
                                                           value="{{ $echoValue('m_mode.'.$id) }}"
                                                           @if($min !== null) data-min="{{ $min }}" @endif
                                                           @if($max !== null) data-max="{{ $max }}" @endif
                                                           @if($auto) readonly @endif
                                                           class="echo-number w-full rounded-l-lg border-gray-300 text-sm {{ $auto ? 'bg-gray-100' : '' }}">
                                                    @if($unit)
                                                        <span class="rounded-r-lg border border-l-0 border-gray-300 bg-gray-100 px-3 py-2 text-xs text-gray-500">{{ $unit }}</span>
                                                    @endif
                                                </div>
                                                <span class="mt-1 block text-[11px] text-gray-500">Ref: {{ $range }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </section>

                                {{-- DOPPLER --}}
                                <section>
                                    <h4 class="text-sm font-semibold uppercase tracking-wide text-gray-700">Doppler Measurements</h4>
                                    <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                        @foreach ([
                                            ['d_e','Mitral E Vel.','m/s','0.6-1.3',false],
                                            ['d_a','Mitral A Vel.','m/s','0.3-0.7',false],
                                            ['d_ea','E/A Ratio','','0.8-2.0',true],
                                            ['d_dt','Deceleration Time','ms','160-240',false],
                                            ['d_sepe',"Septal e'",'cm/s','>7',false],
                                            ['d_late',"Lateral e'",'cm/s','>10',false],
                                            ['d_ee',"E/e' Ratio",'','<14',true],
                                            ['d_trv','TR Vel.','m/s','<2.8',false],
                                            ['d_trg','TR Gradient','mmHg','<30',true],
                                            ['d_rvsp','Est. RVSP','mmHg','<35',true],
                                            ['d_avv','AV Peak Vel.','m/s','<1.7',false],
                                            ['d_avg','AV Peak Grad.','mmHg','<15',true],
                                            ['d_lvotd','LVOT Diameter','cm','1.8-2.2',false],
                                            ['d_lvotvti','LVOT VTI','cm','18-22',false],
                                            ['d_sv','Stroke Volume','ml','60-100',true],
                                            ['d_co','Cardiac Output','L/min','4.0-8.0',true],
                                        ] as [$id,$label,$unit,$range,$auto])
                                            <label class="block">
                                                <span class="text-xs font-semibold text-gray-600">{{ $label }}</span>
                                                <div class="mt-1 flex">
                                                    <input id="{{ $id }}" type="number" step="any"
                                                           name="structured_data[doppler][{{ $id }}]"
                                                           value="{{ $echoValue('doppler.'.$id) }}"
                                                           @if($auto) readonly @endif
                                                           class="echo-number w-full rounded-l-lg border-gray-300 text-sm {{ $auto ? 'bg-gray-100' : '' }}">
                                                    @if($unit)
                                                        <span class="rounded-r-lg border border-l-0 border-gray-300 bg-gray-100 px-3 py-2 text-xs text-gray-500">{{ $unit }}</span>
                                                    @endif
                                                </div>
                                                <span class="mt-1 block text-[11px] text-gray-500">Ref: {{ $range }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </section>

                                {{-- 2D FINDINGS --}}
                                <section>
                                    <h4 class="text-sm font-semibold uppercase tracking-wide text-gray-700">2D Findings</h4>
                                    <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                        @foreach ([
                                            ['fLvSize','LV Size',['Normal','Dilated','Hypertrophied']],
                                            ['fLvSys','LV Systolic Function',['Normal','Mild Dysfunction','Moderate Dysfunction','Severe Dysfunction']],
                                            ['fLvDia','LV Diastolic Function',['Normal','Grade I Diastolic Dysfunction','Grade II Diastolic Dysfunction','Grade III Diastolic Dysfunction']],
                                            ['fRv','RV Size & Function',['Normal','Dilated','Dysfunction']],
                                            ['fLa','LA Size',['Normal','Dilated']],
                                            ['fRa','RA Size',['Normal','Dilated']],
                                            ['fPe','Pericardial Effusion',['Nil','Minimal','Mild','Moderate','Severe']],
                                        ] as [$id,$label,$options])
                                            <label class="block">
                                                <span class="text-xs font-semibold text-gray-600">{{ $label }}</span>
                                                <select id="{{ $id }}" name="structured_data[findings][{{ $id }}]"
                                                        class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm">
                                                    @foreach($options as $option)
                                                        <option value="{{ $option }}" @selected($echoValue('findings.'.$id, $options[0]) === $option)>
                                                            {{ $option }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </label>
                                        @endforeach
                                    </div>
                                    <div class="mt-3 grid gap-3 lg:grid-cols-2">
                                        <label class="block">
                                            <span class="text-xs font-semibold text-gray-600">RWMA</span>
                                            <textarea id="fRwma" name="structured_data[findings][fRwma]" rows="2"
                                                      class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm">{{ $echoValue('findings.fRwma') }}</textarea>
                                        </label>
                                        <label class="block">
                                            <span class="text-xs font-semibold text-gray-600">Other Findings</span>
                                            <textarea id="fOther" name="structured_data[findings][fOther]" rows="2"
                                                      class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm">{{ $echoValue('findings.fOther') }}</textarea>
                                        </label>
                                    </div>
                                </section>

                                {{-- VALVES --}}
                                <section>
                                    <h4 class="text-sm font-semibold uppercase tracking-wide text-gray-700">Valve Assessment</h4>
                                    <div class="mt-3 overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-3 py-2 text-left">Valve</th>
                                                    <th class="px-3 py-2 text-left">Structure</th>
                                                    <th class="px-3 py-2 text-left">Stenosis</th>
                                                    <th class="px-3 py-2 text-left">Regurgitation</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100 bg-white">
                                                @foreach ([
                                                    ['mit','Mitral'],
                                                    ['aor','Aortic'],
                                                    ['tri','Tricuspid'],
                                                    ['pul','Pulmonary'],
                                                ] as [$key,$label])
                                                    <tr>
                                                        <td class="px-3 py-2 font-semibold text-gray-700">{{ $label }}</td>
                                                        <td class="px-3 py-2">
                                                            <input id="v_{{ $key }}_struc"
                                                                   name="structured_data[valves][{{ $key }}][structure]"
                                                                   value="{{ $echoValue('valves.'.$key.'.structure', 'Normal in structure and motion') }}"
                                                                   class="w-full rounded-lg border-gray-300 text-sm">
                                                        </td>
                                                        @foreach(['stenosis' => 'sten', 'regurgitation' => 'reg'] as $field => $suffix)
                                                            <td class="px-3 py-2">
                                                                <select id="v_{{ $key }}_{{ $suffix }}"
                                                                        name="structured_data[valves][{{ $key }}][{{ $field }}]"
                                                                        class="w-full rounded-lg border-gray-300 text-sm">
                                                                    @foreach(['Nil','Trivial','Mild','Mild to Moderate','Moderate','Moderate to Severe','Severe'] as $severity)
                                                                        <option value="{{ $severity }}"
                                                                            @selected($echoValue('valves.'.$key.'.'.$field, 'Nil') === $severity)>
                                                                            {{ $severity }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </td>
                                                        @endforeach
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </section>

                                <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3">
                                    <div class="text-sm font-semibold text-amber-900">Generated Echo Impression</div>
                                    <p class="mt-1 text-xs text-amber-700">
                                        Generated from the rules in your original Echo tool. Review and edit before finalizing.
                                    </p>
                                    <textarea id="echo_auto_impression" rows="5"
                                              class="mt-3 block w-full rounded-lg border-amber-300 bg-white text-sm font-semibold shadow-sm">{{ $echoValue('generated_impression') }}</textarea>
                                    <input type="hidden" id="echo_generated_impression"
                                           name="structured_data[generated_impression]"
                                           value="{{ $echoValue('generated_impression') }}">
                                </div>
                            </div>
                        </div>
                    @endif

                    @if ($isAbdominalUsg)
                        {{-- STRUCTURED USG WHOLE ABDOMEN REPORTING --}}
                        <div
                            id="usg-builder"
                            data-sex="{{ strtolower((string) ($patient?->sex ?? '')) }}"
                            class="mb-7 rounded-xl border border-emerald-200 bg-emerald-50/40"
                        >
                            <div class="border-b border-emerald-200 px-5 py-4">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <h3 class="text-base font-semibold text-emerald-900">
                                            Structured USG Whole Abdomen Reporting
                                        </h3>
                                        <p class="mt-1 text-sm text-emerald-700">
                                            Enter measurements and edit organ descriptions as required.
                                            Generate will populate the standard Findings and Impression fields below.
                                        </p>
                                    </div>

                                    <button
                                        type="button"
                                        id="generate-usg-report"
                                        class="inline-flex shrink-0 justify-center rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800"
                                    >
                                        Generate Findings &amp; Impression
                                    </button>
                                </div>
                            </div>

                            <div class="space-y-6 p-5">

                                <div>
                                    <h4 class="text-sm font-semibold uppercase tracking-wide text-gray-700">
                                        Measurements
                                    </h4>
                                    <p class="mt-1 text-xs text-gray-500">
                                        Optional. Entered values are inserted into the generated report.
                                    </p>

                                    <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                        <label class="block">
                                            <span class="text-xs font-semibold text-gray-600">Liver Size</span>
                                            <div class="mt-1 flex">
                                                <input id="m_liver" type="number" step="any" class="w-full rounded-l-lg border-gray-300 text-sm">
                                                <span class="rounded-r-lg border border-l-0 border-gray-300 bg-gray-100 px-3 py-2 text-sm text-gray-500">cm</span>
                                            </div>
                                        </label>

                                        <label class="block">
                                            <span class="text-xs font-semibold text-gray-600">Spleen Size</span>
                                            <div class="mt-1 flex">
                                                <input id="m_spleen" type="number" step="any" class="w-full rounded-l-lg border-gray-300 text-sm">
                                                <span class="rounded-r-lg border border-l-0 border-gray-300 bg-gray-100 px-3 py-2 text-sm text-gray-500">cm</span>
                                            </div>
                                        </label>

                                        <label class="block">
                                            <span class="text-xs font-semibold text-gray-600">Right Kidney</span>
                                            <div class="mt-1 flex">
                                                <input id="m_rk" type="number" step="any" class="w-full rounded-l-lg border-gray-300 text-sm">
                                                <span class="rounded-r-lg border border-l-0 border-gray-300 bg-gray-100 px-3 py-2 text-sm text-gray-500">cm</span>
                                            </div>
                                        </label>

                                        <label class="block">
                                            <span class="text-xs font-semibold text-gray-600">Left Kidney</span>
                                            <div class="mt-1 flex">
                                                <input id="m_lk" type="number" step="any" class="w-full rounded-l-lg border-gray-300 text-sm">
                                                <span class="rounded-r-lg border border-l-0 border-gray-300 bg-gray-100 px-3 py-2 text-sm text-gray-500">cm</span>
                                            </div>
                                        </label>
                                    </div>

                                    <div id="male-measurements" class="mt-3 hidden grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                        <label class="block">
                                            <span class="text-xs font-semibold text-gray-600">Prostate Volume</span>
                                            <div class="mt-1 flex">
                                                <input id="m_prost" type="number" step="any" class="w-full rounded-l-lg border-gray-300 text-sm">
                                                <span class="rounded-r-lg border border-l-0 border-gray-300 bg-gray-100 px-3 py-2 text-sm text-gray-500">cc</span>
                                            </div>
                                        </label>
                                    </div>

                                    <div id="female-measurements" class="mt-3 hidden grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                        @foreach ([
                                            ['m_ut_l', 'Uterus L', 'cm'],
                                            ['m_ut_w', 'Uterus W', 'cm'],
                                            ['m_ut_ap', 'Uterus AP', 'cm'],
                                            ['m_endo', 'Endometrium', 'mm'],
                                            ['m_ro_l', 'Right Ovary L', 'cm'],
                                            ['m_ro_w', 'Right Ovary W', 'cm'],
                                            ['m_lo_l', 'Left Ovary L', 'cm'],
                                            ['m_lo_w', 'Left Ovary W', 'cm'],
                                        ] as [$id, $label, $unit])
                                            <label class="block">
                                                <span class="text-xs font-semibold text-gray-600">{{ $label }}</span>
                                                <div class="mt-1 flex">
                                                    <input id="{{ $id }}" type="number" step="any" class="w-full rounded-l-lg border-gray-300 text-sm">
                                                    <span class="rounded-r-lg border border-l-0 border-gray-300 bg-gray-100 px-3 py-2 text-sm text-gray-500">{{ $unit }}</span>
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>

                                <div>
                                    <h4 class="text-sm font-semibold uppercase tracking-wide text-gray-700">
                                        Organ Descriptions
                                    </h4>
                                    <p class="mt-1 text-xs text-gray-500">
                                        Standard text is preloaded. Modify any organ description when abnormal findings are present.
                                    </p>

                                    <div class="mt-3 grid gap-4 lg:grid-cols-2">
                                        <label class="block">
                                            <span class="text-sm font-semibold text-gray-700">Liver</span>
                                            <textarea id="t_liver" rows="4" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm">Liver is normal in size ({m_liver} cm) with normal outline and hepatic parenchymal echogenicity. No focal mass lesion seen. No IHBR dilatation seen. MPV, hepatic veins and intrahepatic IVC are normal.</textarea>
                                        </label>

                                        <label class="block">
                                            <span class="text-sm font-semibold text-gray-700">Gall Bladder / CBD</span>
                                            <textarea id="t_gb" rows="4" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm">Gall bladder is well distended with normal wall thickness. Lumen is anechoic. No calculus or mass lesion seen. No pericholecystic fluid. GB hepatic interface is normal. CBD is not dilated. No calculus is seen in CBD.</textarea>
                                        </label>

                                        <label class="block">
                                            <span class="text-sm font-semibold text-gray-700">Pancreas &amp; Spleen</span>
                                            <textarea id="t_panc_spl" rows="4" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm">Pancreas is normal in shape and echotexture, no focal lesion. MPD is not dilated.
Spleen is normal in size ({m_spleen} cm), with normal outline and echotexture. Splenic hilum is normal.</textarea>
                                        </label>

                                        <label class="block">
                                            <span class="text-sm font-semibold text-gray-700">Kidneys / Ureters</span>
                                            <textarea id="t_kidneys" rows="5" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm">Right kidney measures {m_rk} cms and left kidney measures {m_lk} cms.
Both kidneys are normal in size, outline and position. Bilateral cortical parenchymal echogenicity is normal with well-maintained cortico-medullary differentiation. No hydronephrosis, mass or calculus seen in bilateral kidney.
Ureters are not dilated.</textarea>
                                        </label>

                                        <label class="block">
                                            <span class="text-sm font-semibold text-gray-700">Aorta / IVC / Bowel</span>
                                            <textarea id="t_aorta" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm">Abdominal aorta and IVC are normal. No detectable bowel abnormality.</textarea>
                                        </label>

                                        <label class="block">
                                            <span class="text-sm font-semibold text-gray-700">Lymph Nodes / Ascites</span>
                                            <textarea id="t_lymph" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm">No mesenteric or retroperitoneal lymphadenopathy. No ascites.</textarea>
                                        </label>

                                        <label id="male-pelvis" class="hidden lg:col-span-2">
                                            <span class="text-sm font-semibold text-gray-700">Pelvis — Male</span>
                                            <textarea id="t_pelvis_m" rows="4" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm">Urinary bladder is well distended, lumen is anechoic. No calculi or wall thickening noted.
Prostate gland is normal in outline and echotexture, no focal lesion (approx. volume- {m_prost} cc).</textarea>
                                        </label>

                                        <label id="female-pelvis" class="hidden lg:col-span-2">
                                            <span class="text-sm font-semibold text-gray-700">Pelvis — Female</span>
                                            <textarea id="t_pelvis_f" rows="6" class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm">Urinary bladder is well distended, lumen is anechoic. No calculi or wall thickening noted.
Uterus is anteverted and normal in size ({m_ut_l} x {m_ut_w} x {m_ut_ap} cms), with normal outline and echotexture. No focal myometrial lesion. Endometrium is normal and measures {m_endo} mm. Cervix is normal.
Bilateral ovaries are normal in position, size and echotexture. Right ovary measures {m_ro_l} x {m_ro_w} cms. Left ovary measures {m_lo_l} x {m_lo_w} cms. No adnexal mass seen.</textarea>
                                        </label>
                                    </div>
                                </div>

                                <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3">
                                    <div class="text-sm font-semibold text-amber-900">Auto Impression</div>
                                    <p class="mt-1 text-xs text-amber-700">
                                        The original USG rules are retained for hepatomegaly, splenomegaly and prostatomegaly.
                                        Review the generated impression before finalizing.
                                    </p>
                                    <textarea
                                        id="usg_auto_impression"
                                        rows="3"
                                        class="mt-3 block w-full rounded-lg border-amber-300 bg-white text-sm font-semibold shadow-sm"
                                    >NO SIGNIFICANT ABNORMALITY.</textarea>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- EXISTING ERP FINDINGS --}}
                    <div>
                        <label for="findings" class="block text-sm font-semibold text-gray-700">Findings</label>
                        <p class="mt-1 text-xs text-gray-500">
                            @if ($isAbdominalUsg)
                                Generated USG findings appear here. Review and edit them before saving.
                            @elseif ($isEchocardiography)
                                Generated Echo findings appear here. Review and edit them before saving.
                            @else
                                Enter the descriptive findings of the imaging study.
                            @endif
                        </p>
                        <textarea
                            id="findings"
                            name="findings"
                            rows="12"
                            autofocus
                            class="mt-3 block w-full rounded-lg border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500"
                            placeholder="Enter imaging findings..."
                        >{{ old('findings', $existingResult?->findings) }}</textarea>
                    </div>

                    {{-- EXISTING ERP IMPRESSION --}}
                    <div class="mt-6">
                        <label for="impression" class="block text-sm font-semibold text-gray-700">Impression</label>
                        <p class="mt-1 text-xs text-gray-500">
                            Enter or review the conclusion / radiological impression.
                        </p>
                        <textarea
                            id="impression"
                            name="impression"
                            rows="5"
                            class="mt-3 block w-full rounded-lg border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500"
                            placeholder="Enter impression / conclusion..."
                        >{{ old('impression', $existingResult?->impression) }}</textarea>
                    </div>

                    <div class="mt-6 grid gap-4 sm:grid-cols-2">
                        <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-4">
                            <div class="text-sm font-semibold text-blue-800">Save Draft</div>
                            <p class="mt-1 text-sm text-blue-700">
                                Saves the report without completing the imaging study.
                            </p>
                            <p class="mt-2 text-xs text-blue-600">
                                You can return later and continue editing.
                            </p>
                        </div>

                        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-4">
                            <div class="text-sm font-semibold text-amber-800">Finalize Report</div>
                            <p class="mt-1 text-sm text-amber-700">
                                Finalizing marks the imaging investigation as completed.
                            </p>
                            <p class="mt-2 text-xs text-amber-700">
                                Findings or impression must be entered before finalization.
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-col gap-3 border-t border-gray-200 pt-6 sm:flex-row sm:items-center sm:justify-between">
                        <a
                            href="{{ route('imaging.index') }}"
                            class="inline-flex justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                        >
                            Back to Imaging
                        </a>

                        <div class="flex flex-col gap-3 sm:flex-row">
                            <button
                                type="submit"
                                name="action"
                                value="save_draft"
                                class="inline-flex justify-center rounded-lg border border-blue-600 bg-white px-5 py-2.5 text-sm font-semibold text-blue-700 hover:bg-blue-50"
                            >
                                Save Draft
                            </button>

                            <button
                                type="submit"
                                name="action"
                                value="finalize"
                                onclick="return confirm('Finalize this imaging report? The imaging investigation will be marked completed.')"
                                class="inline-flex justify-center rounded-lg bg-green-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-green-700"
                            >
                                Finalize Report
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if ($isEchocardiography)
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const builder = document.getElementById('echo-builder');
                if (!builder) return;

                const num = (id) => {
                    const el = document.getElementById(id);
                    const n = Number.parseFloat(el?.value ?? '');
                    return Number.isFinite(n) ? n : 0;
                };

                const setNumber = (id, value) => {
                    const el = document.getElementById(id);
                    if (!el) return;
                    el.value = Number.isFinite(value) && value > 0 ? value.toFixed(2) : '';
                };

                const textValue = (id) => document.getElementById(id)?.value?.trim() ?? '';

                const calculate = () => {
                    const height = num('e_height');
                    const weight = num('e_weight');
                    if (height && weight) {
                        setNumber('e_bsa', 0.007184 * Math.pow(weight, 0.425) * Math.pow(height, 0.725));
                    }

                    const la = num('m_la');
                    const ao = num('m_aod');
                    if (la && ao) setNumber('m_la_ao', la / ao);

                    const lvidd = num('m_lvidd');
                    const lvids = num('m_lvids');
                    if (lvidd && lvids) setNumber('m_fs', ((lvidd - lvids) / lvidd) * 100);

                    const e = num('d_e');
                    const a = num('d_a');
                    if (e && a) setNumber('d_ea', e / a);

                    const septalE = num('d_sepe');
                    const lateralE = num('d_late');
                    const averageEPrime = septalE && lateralE
                        ? (septalE + lateralE) / 2
                        : (septalE || lateralE || 0);
                    if (e && averageEPrime) setNumber('d_ee', (e * 100) / averageEPrime);

                    const trv = num('d_trv');
                    if (trv) setNumber('d_trg', 4 * trv * trv);

                    const avv = num('d_avv');
                    if (avv) setNumber('d_avg', 4 * avv * avv);

                    const trg = num('d_trg');
                    const rap = num('m_rap');
                    if (trg) setNumber('d_rvsp', trg + (rap || 5));

                    const lvotd = num('d_lvotd');
                    const lvotvti = num('d_lvotvti');
                    if (lvotd && lvotvti) {
                        const sv = Math.PI * Math.pow(lvotd / 2, 2) * lvotvti;
                        setNumber('d_sv', sv);

                        const hr = num('e_hr');
                        if (hr) setNumber('d_co', (sv * hr) / 1000);
                    }

                    autoInterpret();
                };

                const autoInterpret = () => {
                    const lvidd = num('m_lvidd');
                    const ivsd = num('m_ivsd');
                    const pwd = num('m_pwd');

                    if (lvidd > 55) document.getElementById('fLvSize').value = 'Dilated';
                    else if ((ivsd > 11 || pwd > 11) && ivsd > 0) document.getElementById('fLvSize').value = 'Hypertrophied';
                    else if (lvidd > 0) document.getElementById('fLvSize').value = 'Normal';

                    const ef = num('m_ef');
                    if (ef >= 55) document.getElementById('fLvSys').value = 'Normal';
                    else if (ef >= 45) document.getElementById('fLvSys').value = 'Mild Dysfunction';
                    else if (ef >= 30) document.getElementById('fLvSys').value = 'Moderate Dysfunction';
                    else if (ef > 0) document.getElementById('fLvSys').value = 'Severe Dysfunction';

                    const la = num('m_la');
                    if (la > 40) document.getElementById('fLa').value = 'Dilated';
                    else if (la > 0) document.getElementById('fLa').value = 'Normal';

                    const rv = num('m_rv');
                    const tapse = num('m_tapse');
                    if (rv > 30) document.getElementById('fRv').value = 'Dilated';
                    else if (tapse > 0 && tapse < 17) document.getElementById('fRv').value = 'Dysfunction';
                    else if (rv > 0) document.getElementById('fRv').value = 'Normal';

                    const ea = num('d_ea');
                    const ee = num('d_ee');
                    if (ee > 14) document.getElementById('fLvDia').value = 'Grade III Diastolic Dysfunction';
                    else if (ea > 0 && ea < 0.8) document.getElementById('fLvDia').value = 'Grade I Diastolic Dysfunction';
                    else if (ea >= 0.8 && ea <= 2.0 && ee > 0 && ee < 10) document.getElementById('fLvDia').value = 'Normal';
                };

                const valveNames = {
                    mit: 'Mitral',
                    aor: 'Aortic',
                    tri: 'Tricuspid',
                    pul: 'Pulmonary',
                };

                const generate = () => {
                    calculate();

                    const findings = [];
                    const ef = num('m_ef');

                    findings.push(`LV: ${textValue('fLvSize')}, ${textValue('fLvSys')}${ef ? ` (LVEF ${ef}%)` : ''}.`);
                    findings.push(`LV diastolic function: ${textValue('fLvDia')}.`);
                    findings.push(`RV: ${textValue('fRv')}.`);
                    findings.push(`LA: ${textValue('fLa')}; RA: ${textValue('fRa')}.`);
                    findings.push(`Pericardial effusion: ${textValue('fPe')}.`);

                    const rwma = textValue('fRwma');
                    if (rwma) findings.push(`RWMA: ${rwma}.`);

                    const other = textValue('fOther');
                    if (other) findings.push(`Other findings: ${other}.`);

                    const valveNarrative = [];
                    const valveImpressions = [];

                    Object.entries(valveNames).forEach(([key, label]) => {
                        const structure = textValue(`v_${key}_struc`);
                        const stenosis = textValue(`v_${key}_sten`);
                        const regurgitation = textValue(`v_${key}_reg`);

                        valveNarrative.push(
                            `${label}: ${structure}; stenosis ${stenosis}; regurgitation ${regurgitation}.`
                        );

                        if (stenosis !== 'Nil' && stenosis !== 'Normal') {
                            valveImpressions.push(`${stenosis} ${label} Stenosis`);
                        }
                        if (regurgitation !== 'Nil' && regurgitation !== 'Normal') {
                            valveImpressions.push(`${regurgitation} ${label} Regurgitation`);
                        }
                    });

                    findings.push('Valve assessment:');
                    findings.push(...valveNarrative);

                    const impression = [];
                    impression.push(
                        `LV ${textValue('fLvSize').toLowerCase()} with ${textValue('fLvSys').toLowerCase()}${ef ? ` (LVEF ${ef}%)` : ''}.`
                    );

                    if (rwma) impression.push(`RWMA: ${rwma}.`);

                    const diastolic = textValue('fLvDia');
                    if (diastolic && diastolic !== 'Normal') impression.push(`${diastolic}.`);

                    const rv = textValue('fRv');
                    if (rv && rv !== 'Normal') impression.push(`RV ${rv.toLowerCase()}.`);

                    const la = textValue('fLa');
                    if (la && la !== 'Normal') impression.push(`LA ${la.toLowerCase()}.`);

                    const ra = textValue('fRa');
                    if (ra && ra !== 'Normal') impression.push(`RA ${ra.toLowerCase()}.`);

                    if (valveImpressions.length) {
                        impression.push(`Valvular findings: ${valveImpressions.join(', ')}.`);
                    }

                    const rvsp = num('d_rvsp');
                    if (rvsp > 35) {
                        const probability = rvsp > 50 ? 'Severe' : (rvsp > 40 ? 'Moderate' : 'Mild');
                        impression.push(`${probability} Probability of Pulmonary Hypertension (Estimated RVSP: ${rvsp} mmHg).`);
                    }

                    const pe = textValue('fPe');
                    if (pe && pe !== 'Nil') impression.push(`${pe} pericardial effusion noted.`);

                    if (other) impression.push(`Other: ${other}`);

                    const generatedImpression = impression.join('\n');

                    document.getElementById('findings').value = findings.join('\n');
                    document.getElementById('impression').value = generatedImpression;
                    document.getElementById('echo_auto_impression').value = generatedImpression;
                    document.getElementById('echo_generated_impression').value = generatedImpression;

                    document.getElementById('findings')?.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center',
                    });
                };

                builder.querySelectorAll('input, select').forEach((element) => {
                    if (!element.readOnly) {
                        element.addEventListener('input', calculate);
                        element.addEventListener('change', calculate);
                    }
                });

                document.getElementById('generate-echo-report')?.addEventListener('click', generate);

                document.getElementById('echo_auto_impression')?.addEventListener('input', function () {
                    document.getElementById('echo_generated_impression').value = this.value;
                });

                calculate();
            });
        </script>
    @endif

    @if ($isAbdominalUsg)
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const builder = document.getElementById('usg-builder');
                if (!builder) return;

                const patientSex = (builder.dataset.sex || '').toLowerCase();
                const isFemale = patientSex.startsWith('f');
                const isMale = patientSex.startsWith('m');

                const maleMeasurements = document.getElementById('male-measurements');
                const femaleMeasurements = document.getElementById('female-measurements');
                const malePelvis = document.getElementById('male-pelvis');
                const femalePelvis = document.getElementById('female-pelvis');

                if (isFemale) {
                    femaleMeasurements?.classList.remove('hidden');
                    femalePelvis?.classList.remove('hidden');
                } else if (isMale) {
                    maleMeasurements?.classList.remove('hidden');
                    malePelvis?.classList.remove('hidden');
                } else {
                    maleMeasurements?.classList.remove('hidden');
                    femaleMeasurements?.classList.remove('hidden');
                    malePelvis?.classList.remove('hidden');
                    femalePelvis?.classList.remove('hidden');
                }

                const valueOf = (id) => {
                    const element = document.getElementById(id);
                    return element ? element.value.trim() : '';
                };

                const numericValue = (id) => {
                    const value = valueOf(id);
                    if (value === '') return null;
                    const parsed = Number.parseFloat(value);
                    return Number.isFinite(parsed) ? parsed : null;
                };

                const replacePlaceholders = (text) => {
                    const ids = [
                        'm_liver',
                        'm_spleen',
                        'm_rk',
                        'm_lk',
                        'm_prost',
                        'm_ut_l',
                        'm_ut_w',
                        'm_ut_ap',
                        'm_endo',
                        'm_ro_l',
                        'm_ro_w',
                        'm_lo_l',
                        'm_lo_w',
                    ];

                    let output = text;

                    ids.forEach((id) => {
                        const value = valueOf(id) || '___';
                        output = output.replaceAll(`{${id}}`, value);
                    });

                    return output;
                };

                const updateAutoImpression = () => {
                    const impressions = [];

                    const liver = numericValue('m_liver');
                    const spleen = numericValue('m_spleen');
                    const prostate = numericValue('m_prost');

                    const liverText = document.getElementById('t_liver');
                    const pancreasSpleenText = document.getElementById('t_panc_spl');
                    const malePelvisText = document.getElementById('t_pelvis_m');

                    if (liver !== null && liver > 15) {
                        impressions.push('HEPATOMEGALY');
                        if (liverText) {
                            liverText.value = liverText.value.replace(
                                'Liver is normal in size',
                                'Liver is enlarged'
                            );
                        }
                    } else if (liverText) {
                        liverText.value = liverText.value.replace(
                            'Liver is enlarged',
                            'Liver is normal in size'
                        );
                    }

                    if (spleen !== null && spleen > 12) {
                        impressions.push('SPLENOMEGALY');
                        if (pancreasSpleenText) {
                            pancreasSpleenText.value = pancreasSpleenText.value.replace(
                                'Spleen is normal in size',
                                'Spleen is enlarged'
                            );
                        }
                    } else if (pancreasSpleenText) {
                        pancreasSpleenText.value = pancreasSpleenText.value.replace(
                            'Spleen is enlarged',
                            'Spleen is normal in size'
                        );
                    }

                    if (isMale && prostate !== null && prostate > 25) {
                        impressions.push('PROSTATOMEGALY');
                        if (malePelvisText) {
                            malePelvisText.value = malePelvisText.value.replace(
                                'Prostate gland is normal',
                                'Prostate gland is enlarged, but normal'
                            );
                        }
                    } else if (malePelvisText) {
                        malePelvisText.value = malePelvisText.value.replace(
                            'Prostate gland is enlarged, but normal',
                            'Prostate gland is normal'
                        );
                    }

                    const autoImpression = document.getElementById('usg_auto_impression');
                    if (autoImpression) {
                        autoImpression.value = impressions.length
                            ? impressions.join('\n')
                            : 'NO SIGNIFICANT ABNORMALITY.';
                    }
                };

                const generateReport = () => {
                    updateAutoImpression();

                    const sectionIds = [
                        't_liver',
                        't_gb',
                        't_panc_spl',
                        't_kidneys',
                        't_aorta',
                    ];

                    if (isFemale) {
                        sectionIds.push('t_pelvis_f');
                    } else if (isMale) {
                        sectionIds.push('t_pelvis_m');
                    }

                    sectionIds.push('t_lymph');

                    const sections = sectionIds
                        .map((id) => document.getElementById(id))
                        .filter(Boolean)
                        .map((element) => replacePlaceholders(element.value.trim()))
                        .filter((text) => text !== '');

                    const findings = document.getElementById('findings');
                    const impression = document.getElementById('impression');
                    const autoImpression = document.getElementById('usg_auto_impression');

                    if (findings) {
                        findings.value = sections.join('\n\n');
                    }

                    if (impression && autoImpression) {
                        impression.value = autoImpression.value.trim();
                    }

                    findings?.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center',
                    });
                };

                builder.querySelectorAll('input[type="number"]').forEach((input) => {
    input.addEventListener('input', () => {
        updateAutoImpression();

        builder.querySelectorAll('textarea').forEach((textarea) => {
            textarea.value = replacePlaceholders(textarea.value);
        });
    });
});

                document
                    .getElementById('generate-usg-report')
                    ?.addEventListener('click', generateReport);

                updateAutoImpression();
            });
        </script>
    @endif
</x-app-layout>
