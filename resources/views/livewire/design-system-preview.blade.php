<div class="grid min-h-screen lg:grid-cols-[var(--sidebar-w)_1fr]">
    <aside class="bg-brand-900 px-4 py-5 text-[#D8E3EC] lg:sticky lg:top-0 lg:h-screen lg:overflow-auto">
        <div class="mb-4 flex items-center gap-3 border-b border-white/10 pb-5">
            <x-brand-mark />
            <div>
                <div class="text-[15px] font-bold leading-tight text-white">نظام إدارة الجامعة</div>
                <x-ui.ltr class="text-[10px] text-[#8FA6B8]">UMS · DESIGN SYSTEM v1.0</x-ui.ltr>
            </div>
        </div>

        <p class="px-2.5 pb-2 text-[10.5px] font-semibold text-[#7E97AA]">معاينة البنود المعتمدة</p>
        <nav class="flex flex-wrap gap-1 lg:flex-col" aria-label="أقسام معاينة التصميم">
            <a href="#overview" class="rounded-sm px-2.5 py-2 text-small text-[#B9CAD7] hover:bg-white/6 hover:text-white">01 الهوية البصرية</a>
            <a href="#tokens" class="rounded-sm px-2.5 py-2 text-small text-[#B9CAD7] hover:bg-white/6 hover:text-white">02 الرموز البصرية</a>
            <a href="#typography" class="rounded-sm px-2.5 py-2 text-small text-[#B9CAD7] hover:bg-white/6 hover:text-white">03 الطباعة والتخطيط</a>
            <a href="#components" class="rounded-sm px-2.5 py-2 text-small text-[#B9CAD7] hover:bg-white/6 hover:text-white">05 المكوّنات الموحّدة</a>
            <a href="#states" class="rounded-sm px-2.5 py-2 text-small text-[#B9CAD7] hover:bg-white/6 hover:text-white">الحالات واللغات</a>
        </nav>
    </aside>

    <div class="min-w-0">
        <header class="sticky top-0 z-10 flex h-[var(--topbar-h)] items-center justify-between gap-4 border-b border-border-1 bg-surface-0 px-4 lg:px-8">
            <div class="flex items-center gap-2.5 text-small text-ink-500">
                <span class="rounded-full border border-border-1 bg-surface-2 px-3 py-0.5">الحرم الرئيسي</span>
                <span class="hidden rounded-full border border-border-1 bg-surface-2 px-3 py-0.5 sm:inline">الفصل الدراسي الأول</span>
                <strong class="hidden font-semibold text-ink-700 md:inline">معاينة نظام التصميم</strong>
            </div>
            <div class="flex items-center gap-3">
                <x-ui.button wire:click="switchLocale" variant="ghost" size="sm">
                    {{ $previewLocale === 'ar' ? 'English preview' : 'معاينة عربية' }}
                </x-ui.button>
                <div class="grid size-8 place-items-center rounded-full bg-brand-700 text-small font-bold text-white" aria-hidden="true">إد</div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-[var(--content-max)] space-y-6 p-4 lg:p-8">
            {{-- 01 Identity --}}
            <section id="overview" class="scroll-mt-20">
                <p class="mb-1.5 text-micro font-bold tracking-wide text-accent-600">PREMIUM INSTITUTIONAL TECHNOLOGY</p>
                <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <h1 class="m-0 text-h1 font-bold text-ink-900">هوية UMS البصرية</h1>
                        <p class="mt-1 text-small text-ink-500">عربية أصلاً · مؤسسية في المظهر · دقيقة في التفاصيل</p>
                    </div>
                    <x-ui.badge tone="success">معتمد — E-004</x-ui.badge>
                </div>

                <div class="grid gap-6 lg:grid-cols-[2fr_1fr]">
                    <x-ui.card title="العلامة والاستخدام" eyebrow="LOGO LOCKUP">
                        <div class="flex flex-wrap items-center gap-4 rounded-md bg-surface-1 p-4">
                            <x-brand-mark class="size-[72px]" />
                            <div>
                                <div class="text-[22px] font-bold text-brand-900">جامعة [اسم الجامعة]</div>
                                <div class="text-small text-ink-500">نظام إدارة الجامعة · منصة مؤسسية موحّدة</div>
                                <x-ui.ltr class="mt-1.5 block text-[10px] text-accent-600">UNIVERSITY MANAGEMENT SYSTEM</x-ui.ltr>
                            </div>
                        </div>
                        <p class="mt-4 text-small leading-[1.9] text-ink-700">العلامة مستوحاة من قوس أكاديمي وكتاب مفتوح — لا تُنسخ من أي منتج قائم. تُستخدم على خلفية فاتحة فقط، مع مساحة حرة لا تقل عن نصف ارتفاع العلامة.</p>
                    </x-ui.card>

                    <x-ui.card title="قواعد الاستخدام">
                        <x-ui.badge tone="success">مسموح</x-ui.badge>
                        <p class="mt-2 text-small text-ink-700">أحادي اللون على خلفية داكنة · الحجم الأساسي 38px · لمسة ذهبية واحدة.</p>
                        <div class="my-4 h-px bg-border-1"></div>
                        <x-ui.badge tone="danger">ممنوع</x-ui.badge>
                        <p class="mt-2 text-small text-ink-700">تلوين العلامة بألوان الوحدات · إضافة ظل · تشويه النسب · دمجها مع شعار كلية داخل نفس الحقل.</p>
                    </x-ui.card>
                </div>
            </section>

            {{-- 02 Tokens --}}
            <section id="tokens" class="scroll-mt-20">
                <x-ui.card title="رموز التصميم" eyebrow="DESIGN TOKENS">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <h3 class="mb-3 text-h3 font-semibold">لوحة الألوان</h3>
                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                <div class="overflow-hidden rounded-md border border-border-1"><div class="h-14 bg-brand-900"></div><div class="p-2 text-micro"><b class="block">كحل مؤسسي</b><x-ui.ltr>#0B2438</x-ui.ltr></div></div>
                                <div class="overflow-hidden rounded-md border border-border-1"><div class="h-14 bg-brand-700"></div><div class="p-2 text-micro"><b class="block">أزرق تفاعلي</b><x-ui.ltr>#1B5578</x-ui.ltr></div></div>
                                <div class="overflow-hidden rounded-md border border-border-1"><div class="h-14 bg-teal-600"></div><div class="p-2 text-micro"><b class="block">تيركوز أكاديمي</b><x-ui.ltr>#0E7C7B</x-ui.ltr></div></div>
                                <div class="overflow-hidden rounded-md border border-border-1"><div class="h-14 bg-accent-600"></div><div class="p-2 text-micro"><b class="block">ذهبي تحريري</b><x-ui.ltr>#B08A4F</x-ui.ltr></div></div>
                            </div>
                        </div>
                        <div>
                            <h3 class="mb-3 text-h3 font-semibold">المسافات والزوايا</h3>
                            <div class="flex h-16 items-end gap-2">
                                <div class="w-6 bg-brand-600 text-center text-[9px] text-white" style="height: 4px">1</div>
                                <div class="w-6 bg-brand-600 text-center text-[9px] text-white" style="height: 8px">2</div>
                                <div class="w-6 bg-brand-600 text-center text-[9px] text-white" style="height: 12px">3</div>
                                <div class="w-6 bg-brand-600 text-center text-[9px] text-white" style="height: 16px">4</div>
                                <div class="w-6 bg-brand-600 text-center text-[9px] text-white" style="height: 24px">6</div>
                                <div class="w-6 bg-brand-600 text-center text-[9px] text-white" style="height: 32px">8</div>
                                <div class="w-6 bg-brand-600 text-center text-[9px] text-white" style="height: 48px">12</div>
                                <div class="w-6 bg-brand-600 text-center text-[9px] text-white" style="height: 64px">16</div>
                            </div>
                            <div class="mt-4 flex flex-wrap items-center gap-2">
                                <span class="rounded-sm border border-border-1 px-2 py-1 text-micro">radius-sm 4</span>
                                <span class="rounded-md border border-border-1 px-2 py-1 text-micro">radius-md 8</span>
                                <span class="rounded-lg border border-border-1 px-2 py-1 text-micro">radius-lg 12</span>
                                <span class="rounded-md border border-border-1 bg-surface-0 px-2 py-1 shadow-2 text-micro">shadow-2</span>
                            </div>
                        </div>
                    </div>
                </x-ui.card>
            </section>

            {{-- 03 Typography --}}
            <section id="typography" class="scroll-mt-20">
                <x-ui.card title="نظام الطباعة" eyebrow="TYPOGRAPHY">
                    <div class="divide-y divide-border-1">
                        <div class="py-2"><p class="text-micro text-ink-500">DISPLAY · 34px · 700</p><p class="text-display font-bold leading-tight">٣٬٨٤٢ طالباً نشطاً</p></div>
                        <div class="py-2"><p class="text-micro text-ink-500">H1 · 26px · 700</p><p class="text-h1 font-bold">لوحة المؤشرات المؤسسية</p></div>
                        <div class="py-2"><p class="text-micro text-ink-500">H2 · 20px · 650</p><p class="text-h2 font-semibold">توزيع الطلاب حسب الكلية</p></div>
                        <div class="py-2"><p class="text-micro text-ink-500">BODY · 14px · 400 · 1.8</p><p class="text-body">تعرض هذه اللوحة المؤشرات المعتمدة من السجلات الرسمية، مع تمييز المسودات عن السجلات النهائية، واحترام نطاق صلاحية المستخدم الحالي.</p></div>
                        <div class="py-2"><p class="text-micro text-ink-500">SMALL · 12.5px + LTR ISOLATION</p><p class="text-small text-ink-500">آخر تحديث: <x-ui.ltr>2026-11-17 09:42</x-ui.ltr> · الرقم: <x-ui.ltr>STU-2026-0142</x-ui.ltr></p></div>
                    </div>
                </x-ui.card>
            </section>

            {{-- 05 Shared components --}}
            <section id="components" class="scroll-mt-20">
                <x-ui.card title="المكوّنات الموحّدة" eyebrow="SHARED COMPONENTS">
                    <h3 class="mb-3 text-h3 font-semibold">الأزرار</h3>
                    <div class="flex flex-wrap items-center gap-2">
                        <x-ui.button variant="primary" wire:click="recordAction">إجراء مؤكد</x-ui.button>
                        <x-ui.button variant="secondary">إجراء عادي</x-ui.button>
                        <x-ui.button variant="ghost">إجراء خفيف</x-ui.button>
                        <x-ui.button variant="danger">إجراء حذري</x-ui.button>
                        <x-ui.button variant="primary" size="sm">صغير</x-ui.button>
                        <x-ui.button variant="primary" disabled>معطّل</x-ui.button>
                    </div>
                    <p class="mt-2 text-micro text-ink-500">عدد التفاعلات المسجّلة: <x-ui.ltr>{{ $interactionCount }}</x-ui.ltr></p>

                    <div class="my-5 h-px bg-border-1"></div>

                    <h3 class="mb-3 text-h3 font-semibold">شارات الحالة — نص وشكل، لا لون وحده</h3>
                    <div class="flex flex-wrap items-center gap-2">
                        <x-ui.badge tone="success">معتمد</x-ui.badge>
                        <x-ui.badge tone="warning">قيد المراجعة</x-ui.badge>
                        <x-ui.badge tone="danger">مرفوض</x-ui.badge>
                        <x-ui.badge tone="info">مسودة</x-ui.badge>
                        <x-ui.badge tone="neutral">محايد</x-ui.badge>
                    </div>

                    <div class="my-5 h-px bg-border-1"></div>

                    <h3 class="mb-3 text-h3 font-semibold">حقل الإدخال</h3>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-ui.field label="الاسم الكامل" for="preview-name" required help="كما يظهر في السجل الرسمي.">
                            <input id="preview-name" type="text" class="w-full rounded-sm border border-border-2 bg-surface-0 px-2.5 py-2 text-small text-ink-900" placeholder="مثال: أحمد محمد" />
                        </x-ui.field>
                        <x-ui.field label="الرقم الجامعي" for="preview-student-no" help="الأكواد تُعرض داخل حاوية LTR معزولة.">
                            <input id="preview-student-no" type="text" dir="ltr" class="ltr w-full rounded-sm border border-border-2 bg-surface-0 px-2.5 py-2 text-small" value="STU-2026-0142" />
                        </x-ui.field>
                    </div>
                    <x-ui.field label="البريد الإلكتروني" for="preview-email" required error="صيغة البريد غير صحيحة.">
                        <input id="preview-email" type="email" dir="ltr" aria-invalid="true" class="ltr w-full rounded-sm border border-danger bg-[#FFFBFA] px-2.5 py-2 text-small" value="student@example" />
                    </x-ui.field>
                </x-ui.card>
            </section>

            {{-- States and language isolation --}}
            <section id="states" class="scroll-mt-20">
                <div class="grid gap-6 lg:grid-cols-2">
                    <x-ui.card title="جدول بيانات — مثال">
                        <div class="overflow-auto rounded-md border border-border-1">
                            <table class="w-full min-w-[520px] border-collapse text-small">
                                <thead>
                                    <tr class="bg-surface-2 text-start font-semibold text-ink-700">
                                        <th class="border-b border-border-1 px-3 py-2.5 text-start">الطالب</th>
                                        <th class="border-b border-border-1 px-3 py-2.5 text-start">الرقم الجامعي</th>
                                        <th class="border-b border-border-1 px-3 py-2.5 text-start">الحالة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="hover:bg-[#FBFCFD]">
                                        <td class="border-b border-border-1 px-3 py-2.5">أحمد محمد</td>
                                        <td class="border-b border-border-1 px-3 py-2.5"><x-ui.ltr>STU-2026-0142</x-ui.ltr></td>
                                        <td class="border-b border-border-1 px-3 py-2.5"><x-ui.badge tone="success">مسجّل</x-ui.badge></td>
                                    </tr>
                                    <tr>
                                        <td class="px-3 py-2.5">سارة علي</td>
                                        <td class="px-3 py-2.5"><x-ui.ltr>STU-2026-0143</x-ui.ltr></td>
                                        <td class="px-3 py-2.5"><x-ui.badge tone="warning">ناقص مستند</x-ui.badge></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </x-ui.card>

                    <x-ui.card title="الحالات وعزل اللغة" eyebrow="STATES & DIRECTION">
                        <div class="space-y-3 text-small">
                            <div class="rounded-sm border border-dashed border-border-2 p-3 text-center text-ink-500"><b class="mb-1 block text-body text-ink-700">لا توجد نتائج</b>عدّل الفلاتر أو أعد المحاولة.</div>
                            <div class="rounded-sm border border-border-1 bg-info-bg p-3 text-info">المسودات لا تُحتسب في الأرقام المعتمدة.</div>
                            <div dir="{{ $previewLocale === 'ar' ? 'rtl' : 'ltr' }}" class="rounded-sm border border-border-1 bg-surface-1 p-3 text-start">
                                <p class="mb-1 text-micro text-ink-500">{{ $previewLocale === 'ar' ? 'معاينة الاتجاه' : 'Direction preview' }}</p>
                                <p class="font-semibold text-ink-900">{{ $previewLocale === 'ar' ? 'النص العربي في اتجاه RTL، والأكواد معزولة LTR:' : 'English copy in LTR; codes stay isolated:' }} <x-ui.ltr>2026-11-17 · 1,250.00 YER</x-ui.ltr></p>
                            </div>
                        </div>
                    </x-ui.card>
                </div>
            </section>
        </main>
    </div>
</div>
