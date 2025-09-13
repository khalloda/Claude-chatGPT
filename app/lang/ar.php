<?php
/**
 * File: app/lang/ar.php
 * Purpose: Arabic language strings (واجهة عربية) — يجب أن تعكس مفاتيح en.php تمامًا
 * Notes:
 *  - استخدم t('key.path') في القوالب/المتحكمات.
 *  - لا تضع أي أسرار هنا.
 */
return [

    /* =========================
     * App
     * ========================= */
    'app.title'        => 'MI Spare Parts',
    'app.tagline'      => 'إدارة المخزون والمبيعات والمشتريات',
    'app.version'      => 'الإصدار',
    'app.loading'      => 'جاري التحميل…',
    'app.search'       => 'بحث',
    'app.clear'        => 'مسح',
    'app.close'        => 'إغلاق',

    /* =========================
     * Navigation (sidebar / header)
     * ========================= */
    'nav.dashboard'    => 'اللوحة الرئيسية',
    'nav.sales'        => 'المبيعات',
    'nav.purchasing'   => 'المشتريات',
    'nav.inventory'    => 'المخزون',
    'nav.crm'          => 'إدارة العملاء',
    'nav.reports'      => 'التقارير',
    'nav.settings'     => 'الإعدادات',
    'nav.tools'        => 'الأدوات',

    // Sales sub
    'nav.quotes'           => 'عروض الأسعار',
    'nav.sales_orders'     => 'أوامر البيع',
    'nav.invoices'         => 'الفواتير',
    'nav.sales_returns'    => 'مرتجعات المبيعات',
    'nav.payments_in'      => 'المدفوعات الواردة (ذمم مدينة)',

    // Purchasing sub
    'nav.suppliers'        => 'الموردون',
    'nav.purchase_orders'  => 'أوامر الشراء',
    'nav.purchase_invoices'=> 'فواتير الشراء',
    'nav.goods_receipts'   => 'استلام البضائع',
    'nav.purchase_returns' => 'مرتجعات الشراء',

    // Inventory sub
    'nav.products'         => 'المنتجات',
    'nav.categories'       => 'الفئات',
    'nav.makes'            => 'الماركات',
    'nav.models'           => 'الموديلات',
    'nav.warehouses'       => 'المستودعات',
    'nav.transfers'        => 'تحويلات المخزون',
    'nav.adjustments'      => 'تسويات المخزون',
    'nav.reservations'     => 'الحجوزات',
    'nav.low_stock'        => 'انخفاض المخزون',

    // CRM sub
    'nav.clients'          => 'العملاء',
    'nav.contacts'         => 'جهات الاتصال',

    // Reports sub
    'nav.reports_sales'        => 'تقارير المبيعات',
    'nav.reports_purchasing'   => 'تقارير المشتريات',
    'nav.reports_inventory'    => 'تقارير المخزون',
    'nav.reports_ar'           => 'الذمم المدينة',
    'nav.reports_ap'           => 'الذمم الدائنة',

    // Settings sub
    'nav.users_roles'      => 'المستخدمون والصلاحيات',
    'nav.taxes_currency'   => 'الضرائب والعملة',
    'nav.units_sequences'  => 'الوحدات والتسلسل',
    'nav.translations'     => 'الترجمات',
    'nav.notifications'    => 'الإشعارات',
    'nav.integrations'     => 'التكاملات',

    // Tools sub
    'nav.import_export'    => 'الاستيراد والتصدير',
    'nav.backups'          => 'النسخ الاحتياطي',
    'nav.audit_log'        => 'سجل التدقيق',
    'nav.system_health'    => 'فحص النظام',

    /* =========================
     * Actions / Buttons
     * ========================= */
    'action.create'        => 'إنشاء',
    'action.add'           => 'إضافة',
    'action.edit'          => 'تعديل',
    'action.view'          => 'عرض',
    'action.delete'        => 'حذف',
    'action.save'          => 'حفظ',
    'action.cancel'        => 'إلغاء',
    'action.update'        => 'تحديث',
    'action.export'        => 'تصدير',
    'action.print'         => 'طباعة',
    'action.download'      => 'تنزيل',
    'action.back'          => 'رجوع',
    'action.filter'        => 'تصفية',
    'action.reset_filters' => 'إعادة تعيين الفلاتر',
    'action.show_columns'  => 'إظهار/إخفاء الأعمدة',
    'action.more'          => 'المزيد',
    'action.apply'         => 'تطبيق',
    'action.confirm'       => 'تأكيد',
    'action.yes'           => 'نعم',
    'action.no'            => 'لا',

    /* =========================
     * Dashboard KPIs
     * ========================= */
    'kpi.total_stock_value'   => 'قيمة المخزون',
    'kpi.out_of_stock'        => 'نفاد المخزون',
    'kpi.low_stock'           => 'انخفاض المخزون',
    'kpi.quotes_week'         => 'عروض هذا الأسبوع',
    'kpi.orders_week'         => 'أوامر بيع هذا الأسبوع',
    'kpi.invoices_week'       => 'فواتير هذا الأسبوع',
    'kpi.overdue_ar'          => 'ذمم مدينة متأخرة',
    'kpi.best_seller_week'    => 'الأكثر مبيعًا هذا الأسبوع',

    /* =========================
     * Table - generic strings & controls
     * ========================= */
    'table.search_placeholder' => 'ابحث…',
    'table.rows_per_page'      => 'عدد الصفوف لكل صفحة',
    'table.total'              => 'الإجمالي',
    'table.of'                 => 'من',
    'table.showing'            => 'عرض',
    'table.to'                 => 'إلى',
    'table.entries'            => 'سجل',
    'table.no_data'            => 'لا توجد بيانات',
    'table.export_csv'         => 'تصدير CSV',
    'table.export_xlsx'        => 'تصدير XLSX',
    'table.export_pdf'         => 'تصدير PDF',
    'table.refresh'            => 'تحديث',
    'table.actions'            => 'إجراءات',
    'table.pinned_left'        => 'مثبّت يسارًا',
    'table.pinned_right'       => 'مثبّت يمينًا',
    'table.pin'                => 'تثبيت',
    'table.unpin'              => 'إلغاء التثبيت',
    'table.density'            => 'الكثافة',
    'table.density_compact'    => 'مضغوط',
    'table.density_cozy'       => 'مريح',
    'table.show_core_on_mobile'=> 'عرض الأعمدة الأساسية على الجوال',
    'table.truncated_tooltip'  => 'مرّر المؤشر لرؤية النص كاملًا',
    'table.numeric_hint'       => 'القيم الرقمية محاذاة لليمين',

    /* =========================
     * Products (index & forms)
     * ========================= */
    'products.title'           => 'المنتجات',
    'products.code'            => 'الرمز/‏SKU',
    'products.name'            => 'الاسم',
    'products.make_model'      => 'الماركة • الموديل',
    'products.stock_avail'     => 'المتاح',
    'products.stock_resvd'     => 'المحجوز',
    'products.stock_compact'   => 'المتاح / المحجوز',
    'products.price_sell'      => 'السعر',
    'products.status'          => 'الحالة',
    'products.category'        => 'الفئة',
    'products.subcategory'     => 'الفئة الفرعية',
    'products.category_compact'=> 'الفئة → الفرعية',
    'products.supplier'        => 'المورد',
    'products.cost'            => 'التكلفة',
    'products.warehouse_breakdown' => 'المستودعات',
    'products.updated_at'      => 'آخر تحديث',
    'products.create'          => 'إنشاء منتج',
    'products.edit'            => 'تعديل المنتج',
    'products.view'            => 'عرض المنتج',
    'products.delete_confirm'  => 'هل تريد حذف هذا المنتج؟',
    'products.placeholder_code'=> 'أدخل SKU…',
    'products.placeholder_name'=> 'أدخل اسم المنتج…',
    'products.placeholder_price'=> 'أدخل سعر البيع…',

    /* =========================
     * Customers (index & forms)
     * ========================= */
    'customers.title'          => 'العملاء',
    'customers.code'           => 'كود العميل',
    'customers.name'           => 'الاسم',
    'customers.company'        => 'شركة',
    'customers.person'         => 'فرد',
    'customers.phone'          => 'الهاتف',
    'customers.email'          => 'البريد الإلكتروني',
    'customers.city'           => 'المدينة',
    'customers.vat_id'         => 'الرقم الضريبي',
    'customers.salesperson'    => 'مندوب المبيعات',
    'customers.payment_terms'  => 'شروط الدفع',
    'customers.outstanding'    => 'الرصيد المستحق',
    'customers.last_activity'  => 'آخر نشاط',
    'customers.status'         => 'الحالة',
    'customers.create'         => 'إنشاء عميل',
    'customers.edit'           => 'تعديل العميل',
    'customers.view'           => 'عرض العميل',
    'customers.delete_confirm' => 'هل تريد حذف هذا العميل؟',

    /* =========================
     * Sales Invoices (index & forms)
     * ========================= */
    'invoices.title'           => 'الفواتير',
    'invoices.number'          => 'رقم الفاتورة',
    'invoices.date'            => 'التاريخ',
    'invoices.customer'        => 'العميل',
    'invoices.total'           => 'الإجمالي',
    'invoices.paid'            => 'المدفوع',
    'invoices.due'             => 'المتبقي',
    'invoices.paid_due_compact'=> 'المدفوع / المتبقي',
    'invoices.currency'        => 'العملة',
    'invoices.method'          => 'طريقة الدفع',
    'invoices.salesperson'     => 'مندوب المبيعات',
    'invoices.linked_so_quote' => 'أمر/عرض مرتبط',
    'invoices.due_date'        => 'تاريخ الاستحقاق',
    'invoices.created_by'      => 'أنشأها',
    'invoices.status'          => 'الحالة',
    'invoices.create'          => 'إنشاء فاتورة',
    'invoices.view'            => 'عرض الفاتورة',
    'invoices.edit'            => 'تعديل الفاتورة',
    'invoices.delete_confirm'  => 'هل تريد حذف هذه الفاتورة؟',

    /* =========================
     * Purchase Orders
     * ========================= */
    'pos.title'                => 'أوامر الشراء',
    'pos.number'               => 'رقم أمر الشراء',
    'pos.date'                 => 'التاريخ',
    'pos.supplier'             => 'المورد',
    'pos.total'                => 'الإجمالي',
    'pos.received_pct'         => 'نسبة الاستلام',
    'pos.status'               => 'الحالة',
    'pos.expected_date'        => 'تاريخ متوقع',
    'pos.warehouse'            => 'المستودع',
    'pos.created_by'           => 'أنشأها',
    'pos.payment_terms'        => 'شروط الدفع',
    'pos.create'               => 'إنشاء أمر شراء',
    'pos.view'                 => 'عرض أمر الشراء',
    'pos.edit'                 => 'تعديل أمر الشراء',
    'pos.delete_confirm'       => 'هل تريد حذف أمر الشراء؟',

    /* =========================
     * Purchase Invoices
     * ========================= */
    'pis.title'                => 'فواتير الشراء',
    'pis.number'               => 'رقم فاتورة الشراء',
    'pis.date'                 => 'التاريخ',
    'pis.supplier'             => 'المورد',
    'pis.total'                => 'الإجمالي',
    'pis.paid_due'             => 'المدفوع / المتبقي',
    'pis.status'               => 'الحالة',
    'pis.linked_po'            => 'أمر شراء مرتبط',
    'pis.currency'             => 'العملة',
    'pis.grn_ref'              => 'رقم الاستلام (GRN)',
    'pis.created_by'           => 'أنشأها',
    'pis.create'               => 'إنشاء فاتورة شراء',
    'pis.view'                 => 'عرض فاتورة الشراء',
    'pis.edit'                 => 'تعديل فاتورة الشراء',
    'pis.delete_confirm'       => 'هل تريد حذف فاتورة الشراء؟',

    /* =========================
     * Statuses / Badges
     * ========================= */
    'status.active'                => 'نشط',
    'status.inactive'              => 'غير نشط',
    'status.low_stock'             => 'منخفض',
    'status.ok'                    => 'جيد',

    'status.draft'                 => 'مسودة',
    'status.issued'                => 'صادرة',
    'status.paid'                  => 'مدفوعة',
    'status.part_paid'             => 'مدفوعة جزئيًا',
    'status.overdue'               => 'متأخرة',

    'status.open'                  => 'مفتوح',
    'status.closed'                => 'مغلق',
    'status.cancelled'             => 'ملغي',
    'status.partially_received'    => 'تم الاستلام جزئيًا',
    'status.posted'                => 'مُرحَّلة',

    /* =========================
     * Forms / Validation
     * ========================= */
    'form.required'            => 'هذا الحقل مطلوب',
    'form.invalid_email'       => 'بريد إلكتروني غير صالح',
    'form.invalid_phone'       => 'رقم هاتف غير صالح',
    'form.duplicate_sku'       => 'SKU مستخدم من قبل',
    'form.min_length'          => 'قصير جدًا',
    'form.max_length'          => 'طويل جدًا',
    'form.must_be_number'      => 'يجب أن يكون رقمًا',
    'form.must_be_integer'     => 'يجب أن يكون عددًا صحيحًا',
    'form.must_be_positive'    => 'يجب أن يكون موجبًا',
    'form.select_option'       => 'اختر خيارًا…',

    /* =========================
     * Flash / Toasts
     * ========================= */
    'flash.saved'              => 'تم الحفظ بنجاح.',
    'flash.updated'            => 'تم التحديث بنجاح.',
    'flash.deleted'            => 'تم الحذف بنجاح.',
    'flash.error'              => 'حدث خطأ ما.',
    'flash.invalid_session'    => 'جلسة غير صالحة. حاول مرة أخرى.',
    'flash.not_found'          => 'العنصر غير موجود.',
    'flash.confirm_delete'     => 'هل أنت متأكد من حذف هذا العنصر؟',

    /* =========================
     * Print / Documents
     * ========================= */
    'print.title'              => 'طباعة',
    'print.company'            => 'الشركة',
    'print.client'             => 'العميل',
    'print.supplier'           => 'المورد',
    'print.date'               => 'التاريخ',
    'print.subtotal'           => 'الإجمالي الفرعي',
    'print.tax'                => 'الضريبة',
    'print.discount'           => 'الخصم',
    'print.total'              => 'الإجمالي',
    'print.page'               => 'صفحة',
    'print.of'                 => 'من',

    /* =========================
     * Dates / Time
     * ========================= */
    'date.today'               => 'اليوم',
    'date.this_week'           => 'هذا الأسبوع',
    'date.this_month'          => 'هذا الشهر',

    /* =========================
     * Currency labels (display only)
     * ========================= */
    'currency.egp'             => 'جنيه مصري',
    'currency.usd'             => 'دولار أمريكي',

    /* =========================
     * Accessibility / Hints
     * ========================= */
    'a11y.skip_to_content'     => 'تخطي إلى المحتوى',
    'a11y.navigation'          => 'التنقل الرئيسي',
    'hint.truncate'            => 'نص مقطوع؛ مرّر المؤشر لرؤية المزيد',
    'hint.numeric_right'       => 'القيم الرقمية محاذاة لليمين',

    /* =========================
     * Errors (generic)
     * ========================= */
    'error.title'              => 'خطأ',
    'error.404'                => 'الصفحة غير موجودة',
    'error.500'                => 'خطأ في الخادم',
    'error.permission'         => 'ليست لديك صلاحية لتنفيذ هذا الإجراء',

    /* =========================
     * Translations & Localization
     * ========================= */
    'translations.title'       => 'إدارة الترجمة',
    'translations.description' => 'إدارة ترجمات النظام وإعدادات اللغة',
    'translations.current_language' => 'اللغة الحالية',
    'translations.switch_language' => 'تغيير اللغة',
    'translations.translation_keys' => 'مفاتيح الترجمة',
    'translations.total_keys'  => 'إجمالي مفاتيح الترجمة',
    'translations.translated_keys' => 'المفاتيح المترجمة',
    'translations.missing_keys' => 'الترجمات المفقودة',
    'translations.progress'    => 'تقدم الترجمة',
    'translations.search'      => 'البحث في مفاتيح الترجمة...',
    'translations.key'         => 'المفتاح',
    'translations.english'     => 'الإنجليزية',
    'translations.arabic'      => 'العربية',
    'translations.status'      => 'الحالة',
    'translations.translated'  => 'مترجم',
    'translations.not_translated' => 'غير مترجم',
    'translations.export'      => 'تصدير للترجمة',
    'translations.stats'       => 'إحصائيات الترجمة',
    'translations.coverage'    => 'تغطية الترجمة',
    'translations.language_info' => 'معلومات اللغة',
    'translations.default_language' => 'اللغة الافتراضية',
    'translations.secondary_language' => 'اللغة الثانوية',
    'translations.completion_rate' => 'معدل الإنجاز',
    'translations.missing_categories' => 'فئات الترجمة المفقودة',
    'translations.direction_ltr' => 'من اليسار إلى اليمين',
    'translations.direction_rtl' => 'من اليمين إلى اليسار',
    'translations.text_direction' => 'اتجاه النص',

    /* =========================
     * User Management & RBAC
     * ========================= */
    'users.title'              => 'إدارة المستخدمين',
    'users.user_management'    => 'إدارة المستخدمين',
    'users.add_user'           => 'إضافة مستخدم',
    'users.create_new_user'    => 'إنشاء مستخدم جديد',
    'users.back_to_users'      => 'العودة للمستخدمين',
    'users.basic_information'  => 'المعلومات الأساسية',
    'users.email_or_user_id'   => 'البريد الإلكتروني أو معرف المستخدم',
    'users.all_statuses'       => 'جميع الحالات',
    'users.all_roles'          => 'جميع الأدوار',
    'users.legacy_role'        => 'الدور القديم',
    'users.add_permission'     => 'إضافة صلاحية',
    'users.no_permissions_found' => 'لم يتم العثور على صلاحيات',
    'users.showing_users' => 'عرض {count} من {total} مستخدم',
    'users.page_info' => 'صفحة {current} من {pages}',
    'users.rbac_roles' => 'أدوار RBAC',
    'users.created' => 'تم الإنشاء',
    'users.no_users_found_criteria' => 'لم يتم العثور على مستخدمين يطابقون معاييرك.',
    'users.no_rbac_roles' => 'لا توجد أدوار RBAC',
    'users.never' => 'أبداً',
    'users.status_active' => 'نشط',
    'users.status_inactive' => 'غير نشط',
    'users.status_suspended' => 'معلق',
    'users.status_pending' => 'في الانتظار',
    'users.list'               => 'المستخدمون',
    'users.create'             => 'إنشاء مستخدم',
    'users.edit'               => 'تعديل مستخدم',
    'users.view'               => 'عرض مستخدم',
    'users.delete'             => 'حذف مستخدم',
    'users.status'             => 'الحالة',
    'users.roles'              => 'الأدوار',
    'users.permissions'        => 'الصلاحيات',
    'users.last_login'         => 'آخر تسجيل دخول',
    'users.search_placeholder' => 'البحث بالبريد الإلكتروني أو المعرف...',
    'users.no_users'           => 'لا يوجد مستخدمون',
    'users.profile'            => 'ملف المستخدم',
    'users.activity'           => 'نشاط المستخدم',

    'roles.title'              => 'إدارة الأدوار',
    'roles.create'             => 'إنشاء دور',
    'roles.edit'               => 'تعديل دور',
    'roles.permissions'        => 'صلاحيات الدور',

    'permissions.title'        => 'إدارة الصلاحيات',
    'permissions.category'     => 'الفئة',
    'permissions.description'  => 'الوصف',

    /* =========================
     * Settings & Configuration
     * ========================= */
    'settings.title'           => 'الإعدادات',
    'settings.tax_currency'    => 'إعدادات الضرائب والعملة',
    'settings.tax_rates'       => 'إدارة معدلات الضرائب',
    'settings.currencies'      => 'إدارة العملات',
    'settings.company_info'    => 'معلومات الشركة',
    'settings.currency_display' => 'عرض العملة',
    'settings.tax_configuration' => 'إعداد الضرائب',
    'settings.manage_rates'    => 'إدارة معدلات الضرائب',
    'settings.manage_currencies' => 'إدارة العملات',
    'settings.current_rates'   => 'معدلات الضرائب الحالية',
    'settings.current_currencies' => 'العملات الحالية',
    'settings.base_currency'   => 'العملة الأساسية',
    'settings.exchange_rate'   => 'سعر الصرف',
    'settings.currency_converter' => 'محول العملات',

    /* =========================
     * Dashboard
     * ========================= */
    'dashboard.total_stock_value' => 'إجمالي قيمة المخزون',
    'dashboard.out_of_stock'    => 'نفد المخزون',
    'dashboard.low_stock'       => 'مخزون منخفض',
    'dashboard.quotes_this_week' => 'عروض الأسعار (هذا الأسبوع)',
    'dashboard.orders_this_week' => 'أوامر المبيعات (هذا الأسبوع)',
    'dashboard.invoices_this_week' => 'الفواتير (هذا الأسبوع)',
    'dashboard.best_seller_week' => 'الأكثر مبيعاً (هذا الأسبوع)',
    'dashboard.activity'        => 'النشاط',
    'dashboard.last_30_days'    => 'آخر 30 يوماً',
    'dashboard.charts_placeholder' => 'ستظهر الرسوم البيانية هنا (المبيعات مقابل المشتريات، أفضل المنتجات، إلخ).',
    'dashboard.connect_metrics' => 'قم بربط المقاييس الحقيقية لاستبدال هذا العنصر النائب.',
    'dashboard.sales_vs_purchases' => 'المبيعات مقابل المشتريات',
    'dashboard.top_products' => 'أفضل المنتجات (آخر 30 يوم)',
    'dashboard.sales' => 'المبيعات',
    'dashboard.purchases' => 'المشتريات',
    'dashboard.date' => 'التاريخ',
    'dashboard.amount' => 'المبلغ',
    'dashboard.value' => 'القيمة',
    'dashboard.quotes_today' => 'عروض الأسعار اليوم',
    'dashboard.orders_today' => 'الطلبات اليوم',
    'dashboard.invoices_today' => 'الفواتير اليوم',
    'dashboard.purchases_today' => 'المشتريات اليوم',
    'dashboard.alerts'          => 'التنبيهات',

    // Import/Export
    'importexport.import_export' => 'الاستيراد/التصدير',
    'importexport.export_data' => 'تصدير البيانات',
    'importexport.export_type' => 'نوع التصدير',
    'importexport.bulk_export' => 'تصدير شامل',
    'importexport.module_export' => 'تصدير الوحدة',
    'importexport.custom_export' => 'تصدير مخصص',
    'importexport.export_type_help' => 'اختر نوع التصدير الذي تريد تنفيذه',
    'importexport.select_module' => 'اختر الوحدة',
    'importexport.choose_module' => 'اختر وحدة...',
    'importexport.all_modules' => 'جميع الوحدات',
    'importexport.inventory' => 'المخزون',
    'importexport.products' => 'المنتجات',
    'importexport.categories' => 'الفئات',
    'importexport.makes' => 'العلامات التجارية',
    'importexport.models' => 'الموديلات',
    'importexport.warehouses' => 'المستودعات',
    'importexport.sales' => 'المبيعات',
    'importexport.customers' => 'العملاء',
    'importexport.quotes' => 'عروض الأسعار',
    'importexport.orders' => 'الطلبات',
    'importexport.invoices' => 'الفواتير',
    'importexport.purchasing' => 'المشتريات',
    'importexport.suppliers' => 'الموردون',
    'importexport.purchase_orders' => 'أوامر الشراء',
    'importexport.purchase_invoices' => 'فواتير الشراء',
    'importexport.system' => 'النظام',
    'importexport.users' => 'المستخدمون',
    'importexport.export_format' => 'تنسيق التصدير',
    'importexport.export_info' => 'معلومات التصدير',
    'importexport.supported_formats' => 'التنسيقات المدعومة',
    'importexport.csv_description' => 'قيم مفصولة بفواصل، متوافق مع Excel',
    'importexport.excel_description' => 'تنسيق Microsoft Excel',
    'importexport.pdf_description' => 'تنسيق المستندات المحمولة للطباعة',
    'importexport.export_types' => 'أنواع التصدير',
    'importexport.bulk_description' => 'تصدير جميع البيانات من الوحدة المحددة',
    'importexport.module_description' => 'تصدير بيانات وحدة محددة مع العلاقات',
    'importexport.custom_description' => 'تصدير مع فلاتر واختيارات مخصصة',
    'importexport.note' => 'ملاحظة',
    'importexport.export_note' => 'قد تستغرق عمليات التصدير الكبيرة وقتاً للمعالجة. يرجى التحلي بالصبر.',
    'importexport.quick_export' => 'تصدير سريع',
    'importexport.export_products' => 'تصدير المنتجات',
    'importexport.export_customers' => 'تصدير العملاء',
    'importexport.export_invoices' => 'تصدير الفواتير',
    'importexport.export_all' => 'تصدير جميع البيانات',
    'importexport.please_select_module' => 'يرجى اختيار وحدة للتصدير',
    'importexport.please_select_format' => 'يرجى اختيار تنسيق التصدير',
    'importexport.exporting' => 'جاري التصدير',
    'dashboard.low_stock_items' => 'عناصر المخزون المنخفض',
    'dashboard.products_below_threshold' => 'منتجات تحت الحد الأدنى',
    'dashboard.units'           => 'وحدة',
    'dashboard.overdue_ar'      => 'الذمم المتأخرة',
    'dashboard.invoices_overdue' => 'فواتير متأخرة',
    'dashboard.system_status'   => 'حالة النظام',
    'dashboard.system_wired'    => 'الموجه والمتحكم والعرض والتخطيط متصلة.',
    'dashboard.database_connection' => 'اتصال قاعدة البيانات:',
    'dashboard.health_check'    => 'فحص الصحة:',

    /* =========================
     * Navigation & Common UI
     * ========================= */
    'nav.dashboard'            => 'لوحة التحكم',
    'nav.sales'                => 'المبيعات',
    'nav.quotes'               => 'عروض الأسعار',
    'nav.orders'               => 'أوامر المبيعات',
    'nav.invoices'             => 'الفواتير',
    'nav.sales_returns'        => 'مرتجعات المبيعات',
    'nav.payments'             => 'المدفوعات الواردة',
    'nav.purchasing'           => 'المشتريات',
    'nav.suppliers'            => 'الموردون',
    'nav.purchase_orders'      => 'أوامر الشراء',
    'nav.purchase_invoices'    => 'فواتير الشراء',
    'nav.goods_receipts'       => 'إيصالات البضائع',
    'nav.purchase_returns'     => 'مرتجعات المشتريات',
    'nav.supplier_payments'    => 'المدفوعات الصادرة',
    'nav.inventory'            => 'المخزون',
    'nav.products'             => 'المنتجات',
    'nav.categories'           => 'الفئات',
    'nav.makes'                => 'الماركات',
    'nav.models'               => 'الموديلات',
    'nav.warehouses'           => 'المستودعات',
    'nav.transfers'            => 'التحويلات',
    'nav.adjustments'          => 'التعديلات',
    'nav.reservations'         => 'الحجوزات',
    'nav.low_stock'            => 'المخزون المنخفض',
    'nav.crm'                  => 'إدارة العملاء',
    'nav.customers'            => 'العملاء',
    'nav.contacts'             => 'جهات الاتصال',
    'nav.reports'              => 'التقارير',
    'nav.sales_reports'        => 'تقارير المبيعات',
    'nav.purchasing_reports'   => 'تقارير المشتريات',
    'nav.inventory_reports'    => 'تقارير المخزون',
    'nav.ar_reports'           => 'تقارير الذمم المدينة',
    'nav.ap_reports'           => 'تقارير الذمم الدائنة',
    'nav.settings'             => 'الإعدادات',
    'nav.users'                => 'المستخدمون والأدوار',
    'nav.tax_currency'         => 'الضرائب والعملة',
    'nav.units_sequences'      => 'الوحدات والتسلسل',
    'nav.translations'         => 'الترجمات',
    'nav.notifications'        => 'الإشعارات',
    'nav.integrations'         => 'التكاملات',
    'nav.tools'                => 'الأدوات',
    'nav.import_export'        => 'الاستيراد والتصدير',
    'nav.backups'              => 'النسخ الاحتياطية',
    'nav.audit_log'            => 'سجل التدقيق',
    'nav.system_health'        => 'صحة النظام',
    'nav.logout'               => 'تسجيل خروج',
    'nav.language'             => 'اللغة',

    'table.search_placeholder' => 'بحث...',
    
    'common.ok'                => 'حسناً',
    'common.failed'            => 'فشل',
    'common.email'             => 'البريد الإلكتروني',
    'common.password'          => 'كلمة المرور',
    'common.name'              => 'الاسم',
    'common.phone'             => 'الهاتف',
    'common.address'           => 'العنوان',
    'common.actions'           => 'الإجراءات',
    'common.status'            => 'الحالة',
    'common.total'             => 'الإجمالي',
    'common.view'              => 'عرض',
    'common.edit'              => 'تعديل',
    'common.delete'            => 'حذف',
    'common.search'            => 'بحث',
    'common.create'            => 'إنشاء',
    'common.save'              => 'حفظ',
    'common.save_changes'      => 'حفظ التغييرات',
    'common.return_home'       => 'العودة للرئيسية',
    'common.back'              => 'العودة',
    'common.close'             => 'إغلاق',
    'common.cancel'            => 'إلغاء',
    'common.confirm'           => 'تأكيد',
    'common.update'            => 'تحديث',

    /* =========================
     * Authentication
     * ========================= */
    'auth.login'               => 'تسجيل الدخول',
    'auth.enter_credentials'   => 'أدخل بيانات الاعتماد الخاصة بك.',
    'auth.sign_in'             => 'تسجيل الدخول',
    'auth.logout'              => 'تسجيل الخروج',
    
    /* =========================
     * Error Messages
     * ========================= */
    'errors.404_title'         => '404 — غير موجود',
    'errors.404_message'       => 'الصفحة التي طلبتها غير موجودة.',
    'errors.access_denied'     => 'تم رفض الوصول',
    'errors.access_denied_message' => 'ليس لديك إذن للوصول إلى هذا المورد. قد يكون هذا بسبب:',
    'errors.common_reasons'    => 'الأسباب الشائعة:',
    'errors.not_logged_in'     => 'لم تقم بتسجيل الدخول',
    'errors.insufficient_permissions' => 'ليس لديك الأذونات المطلوبة',
    'errors.session_expired'   => 'انتهت صلاحية جلستك',
    'errors.authentication_required' => 'المورد يتطلب المصادقة',
    'errors.contact_admin_message' => 'إذا كنت تعتقد أن هذا خطأ، يرجى <a href="/login">تسجيل الدخول</a> أو الاتصال بالمسؤول.',
    'common.save'              => 'حفظ',
    'common.export'            => 'تصدير',
    'common.import'            => 'استيراد',
    'common.filter'            => 'تصفية',
    'common.reset'             => 'إعادة تعيين',

    /* =========================
     * Products
     * ========================= */
    'products.search_placeholder' => 'البحث بالاسم أو الكود',
    'products.all_categories'  => 'جميع الفئات',
    'products.all_makes'       => 'جميع الماركات',
    'products.all_models'      => 'جميع الموديلات',
    'products.new_product'     => '+ منتج جديد',
    'products.code'            => 'الكود',
    'products.category'        => 'الفئة',
    'products.make_model'      => 'الماركة / الموديل',
    'products.cost'            => 'التكلفة',
    'products.price'           => 'السعر',
    'products.availability'    => 'المتاح / المحجوز',
    'products.stock'           => 'المخزون',
    'products.delete_confirm'  => 'حذف هذا المنتج؟',
    'products.no_products'     => 'لا توجد منتجات بعد.',

    /* =========================
     * Customers
     * ========================= */
    'customers.new_customer'   => '+ عميل جديد',
    'customers.edit_customer'  => 'تعديل العميل',
    'customers.customer'       => 'العميل',
    'customers.statement'      => 'كشف الحساب',
    'customers.delete_confirm' => 'حذف هذا العميل؟',
    'customers.no_customers'   => 'لا يوجد عملاء بعد.',
    'customers.ar_totals'      => 'إجمالي الذمم المدينة',
    'customers.invoices'       => 'الفواتير',
    'customers.payments'       => 'المدفوعات',
    'customers.credits'        => 'الائتمانات',
    'customers.balance'        => 'الرصيد',
    'customers.view_statement' => 'عرض كشف الحساب',
    'customers.back_to_customers' => 'العودة للعملاء',
    'customers.sales_orders'   => 'أوامر البيع',

    /* =========================
     * Invoices
     * ========================= */
    'invoices.invoice_number'  => 'رقم الفاتورة',
    'invoices.customer'        => 'العميل',
    'invoices.paid'           => 'مدفوع',
    'invoices.no_invoices'    => 'لا توجد فواتير بعد.',
    'invoices.invoice'        => 'الفاتورة',
    'invoices.credits'        => 'الائتمانات',
    'invoices.balance'        => 'الرصيد',
    'invoices.confirm_delivered' => 'تأكيد التسليم',
    'invoices.print'          => 'طباعة',
    'invoices.product'        => 'المنتج',
    'invoices.warehouse'      => 'المستودع',
    'invoices.quantity'       => 'الكمية',
    'invoices.unit_price'     => 'سعر الوحدة',
    'invoices.line_total'     => 'إجمالي السطر',

    /* =========================
     * Orders
     * ========================= */
    'orders.order_number'     => 'رقم الطلب',
    'orders.customer'         => 'العميل',
    'orders.no_orders'        => 'لا توجد طلبات بعد.',
    'orders.sales_order'      => 'طلب البيع',
    'orders.create_invoice'   => 'إنشاء فاتورة',
    'orders.print'            => 'طباعة',
    'orders.back_to_orders'   => 'العودة للطلبات',
    'orders.product'          => 'المنتج',
    'orders.warehouse'        => 'المستودع',
    'orders.quantity'         => 'الكمية',
    'orders.unit_price'       => 'سعر الوحدة',
    'orders.line_total'       => 'إجمالي السطر',

    /* =========================
     * Quotes
     * ========================= */
    'quotes.new_quote'        => '+ عرض سعر جديد',
    'quotes.quote_number'     => 'رقم العرض',
    'quotes.customer'         => 'العميل',
    'quotes.no_quotes'        => 'لا توجد عروض أسعار بعد.',
    'quotes.quote'            => 'عرض السعر',
    'quotes.subtotal'         => 'المجموع الفرعي',
    'quotes.tax'              => 'الضريبة',
    'quotes.expires_at'       => 'ينتهي في',
    'quotes.product'          => 'المنتج',
    'quotes.warehouse'        => 'المستودع',
    'quotes.quantity'         => 'الكمية',
    'quotes.price'            => 'السعر',
    'quotes.line_total'       => 'إجمالي السطر',

    /* =========================
     * Suppliers
     * ========================= */
    'suppliers.back_to_purchase_orders' => 'العودة لأوامر الشراء',
    'suppliers.balance'       => 'الرصيد',
    'suppliers.statement'     => 'كشف الحساب',
    'suppliers.no_suppliers'  => 'لا يوجد موردون.',
    'suppliers.new_supplier'  => 'مورد جديد',
    'suppliers.edit_supplier' => 'تعديل المورد',
    'suppliers.supplier'      => 'المورد',
    'suppliers.ap_totals'     => 'إجمالي الذمم الدائنة',
    'suppliers.invoices'      => 'الفواتير',
    'suppliers.payments'      => 'المدفوعات',
    'suppliers.credits'       => 'الائتمانات',
    'suppliers.view_statement' => 'عرض كشف الحساب',
    'suppliers.back_to_suppliers' => 'العودة للموردين',
    'suppliers.purchase_orders' => 'أوامر الشراء',
    'suppliers.delivered_items' => 'العناصر المسلمة (الإيصالات)',
    'suppliers.purchase_invoices' => 'فواتير الشراء',
    'suppliers.payments_ap'   => 'المدفوعات (ذمم دائنة)',

    /* =========================
     * Warehouses
     * ========================= */
    'warehouses.new_warehouse' => '+ مستودع جديد',
    'warehouses.edit_warehouse' => 'تعديل المستودع',
    'warehouses.code'         => 'الكود',
    'warehouses.location'     => 'الموقع',
    'warehouses.location_optional' => 'الموقع (اختياري)',
    'warehouses.on_hand'      => 'متوفر',
    'warehouses.reserved'     => 'محجوز',
    'warehouses.value'        => 'القيمة',

    /* =========================
     * Payments
     * ========================= */
    'payments.back_to_invoices' => 'العودة للفواتير',
    'payments.date'           => 'التاريخ',
    'payments.payment_number' => 'رقم الدفع',
    'payments.invoice'        => 'الفاتورة',
    'payments.customer'       => 'العميل',
    'payments.method'         => 'الطريقة',
    'payments.reference'      => 'المرجع',
    'payments.amount'         => 'المبلغ',
    'payments.new_for_invoice' => 'جديد لهذه الفاتورة',
    'payments.no_payments'    => 'لا توجد مدفوعات بعد.',
    'payments.new_payment'    => 'دفع جديد',
    'payments.paid'           => 'مدفوع',
    'payments.note'           => 'ملاحظة',
    'payments.save_payment'   => 'حفظ الدفع',
    'payments.back_to_invoice' => 'العودة للفاتورة',
    'payments.payments_list'  => 'قائمة المدفوعات',

    /* =========================
     * Reports
     * ========================= */
    'reports.sales_report'    => 'تقرير المبيعات',
    'reports.ar_aging'        => 'تقدم الذمم المدينة',
    'reports.from'            => 'من',
    'reports.to'              => 'إلى',
    'reports.as_of'           => 'حتى',
    'reports.apply'           => 'تطبيق',
    'reports.print'           => 'طباعة',
    'reports.total_invoices'  => 'إجمالي الفواتير',
    'reports.total_returns'   => 'إجمالي المرتجعات',
    'reports.net_sales'       => 'صافي المبيعات',
    'reports.payments_received' => 'المدفوعات المستلمة',
    'reports.date'            => 'التاريخ',
    'reports.type'            => 'النوع',
    'reports.reference'       => 'المرجع',
    'reports.customer'        => 'العميل',
    'reports.debit'           => 'مدين',
    'reports.credit'          => 'دائن',
    'reports.age_0_30'        => '0–30',
    'reports.age_31_60'       => '31–60',
    'reports.age_61_90'       => '61–90',
    'reports.age_90_plus'     => '90+',

    /* =========================
     * Sales Returns
     * ========================= */
    'salesreturns.credit_notes' => 'إشعارات الائتمان (مرتجعات المبيعات)',
    'salesreturns.back_to_invoices' => 'العودة للفواتير',
    'salesreturns.credit_number' => 'رقم الائتمان',
    'salesreturns.invoice_number' => 'رقم الفاتورة',
    'salesreturns.customer'   => 'العميل',
    'salesreturns.date'       => 'التاريخ',
    'salesreturns.no_credit_notes' => 'لا توجد إشعارات ائتمان بعد.',

    /* =========================
     * Purchase Orders
     * ========================= */
    'purchaseorders.purchase_orders' => 'أوامر الشراء',
    'purchaseorders.new_po'   => 'أمر شراء جديد',
    'purchaseorders.po_number' => 'رقم أمر الشراء',
    'purchaseorders.supplier' => 'المورد',
    'purchaseorders.no_purchase_orders' => 'لا توجد أوامر شراء بعد.',

    /* =========================
     * Categories
     * ========================= */
    'categories.categories'   => 'الفئات',
    'categories.new_category' => '+ فئة جديدة',
    'categories.slug'         => 'المعرف',
    'categories.parent'       => 'الفئة الأب',

    /* =========================
     * User Management
     * ========================= */
    'users.roles'             => 'الأدوار',
    'users.new_role'          => 'دور جديد',
    'users.slug'              => 'المعرف',
    'users.delete_role_confirm' => 'حذف هذا الدور؟',
    'users.no_roles'          => 'لا توجد أدوار.',

    /* =========================
     * Purchase Invoices
     * ========================= */
    'purchaseinvoices.purchase_invoices' => 'فواتير الشراء',
    'purchaseinvoices.back_to_purchase_orders' => 'العودة لأوامر الشراء',
    'purchaseinvoices.pi_number' => 'رقم فاتورة الشراء',
    'purchaseinvoices.supplier' => 'المورد',
    'purchaseinvoices.po_number' => 'رقم أمر الشراء',
    'purchaseinvoices.no_purchase_invoices' => 'لا توجد فواتير شراء بعد.',

    /* =========================
     * Makes & Models
     * ========================= */
    'makes.makes'             => 'العلامات التجارية',
    'makes.new_make'          => '+ علامة تجارية جديدة',
    'makes.slug'              => 'المعرف',
    'models.models'           => 'الموديلات',
    'models.filter_by_make'   => 'تصفية حسب العلامة التجارية',
    'models.new_model'        => '+ موديل جديد',

    /* =========================
     * Stock Transfers
     * ========================= */
    'transfers.stock_transfers' => 'تحويلات المخزون',
    'transfers.new_transfer'  => 'تحويل جديد',
    'transfers.tr_number'     => 'رقم التحويل',
    'transfers.date'          => 'التاريخ',
    'transfers.from'          => 'من',
    'transfers.to'            => 'إلى',
    'transfers.open'          => 'فتح',
    'transfers.no_transfers'  => 'لا توجد تحويلات بعد.',

    /* =========================
     * Goods Receipts
     * ========================= */
    'receipts.goods_receipts' => 'إيصالات البضائع (GRN)',
    'receipts.most_recent_receipts' => 'أحدث الإيصالات عبر جميع فواتير الشراء.',
    'receipts.date'          => 'التاريخ',
    'receipts.pi_number'     => 'رقم فاتورة الشراء',
    'receipts.product'       => 'المنتج',
    'receipts.warehouse'     => 'المستودع',
    'receipts.quantity'      => 'الكمية',
    'receipts.unit_cost'     => 'تكلفة الوحدة',

    /* =========================
     * Purchase Returns
     * ========================= */
    'purchasereturns.purchase_returns' => 'مرتجعات الشراء (إشعارات الخصم)',
    'purchasereturns.most_recent_returns' => 'أحدث مرتجعات الشراء عبر جميع فواتير الشراء.',
    'purchasereturns.date'   => 'التاريخ',
    'purchasereturns.pr_number' => 'رقم مرتجع الشراء',
    'purchasereturns.pi_number' => 'رقم فاتورة الشراء',
    'purchasereturns.supplier' => 'المورد',

    /* =========================
     * Supplier Payments
     * ========================= */
    'supplierpayments.supplier_payments' => 'مدفوعات الموردين',
    'supplierpayments.back_to_purchase_invoices' => 'العودة لفواتير الشراء',
    'supplierpayments.date'  => 'التاريخ',
    'supplierpayments.supplier' => 'المورد',
    'supplierpayments.pi_number' => 'رقم فاتورة الشراء',
    'supplierpayments.method' => 'الطريقة',
    'supplierpayments.reference' => 'المرجع',
    'supplierpayments.amount' => 'المبلغ',
    'supplierpayments.no_supplier_payments' => 'لا توجد مدفوعات موردين بعد.',

    /* =========================
     * Stock Adjustments
     * ========================= */
    'adjustments.stock_adjustments' => 'تعديلات المخزون',
    'adjustments.new_adjustment' => 'تعديل جديد',
    'adjustments.ad_number'  => 'رقم التعديل',
    'adjustments.date'       => 'التاريخ',
    'adjustments.warehouse'  => 'المستودع',
    'adjustments.reason'     => 'السبب',
    'adjustments.open'       => 'فتح',
    'adjustments.no_adjustments' => 'لا توجد تعديلات بعد.',

    /* =========================
     * Reservations
     * ========================= */
    'reservations.reservations' => 'الحجوزات',
    'reservations.description' => 'يعرض المستندات التي تحتفظ بحجوزات المخزون حالياً. العروض تحجز عند إرسالها. الطلبات تحجز بعد التحويل من العروض. تأكيد التسليم في الفاتورة يحرر حجوزات الطلبات.',
    'reservations.all_customers' => 'جميع العملاء',
    'reservations.all_types' => 'جميع الأنواع',

    /* =========================
     * Low Stock
     * ========================= */
    'lowstock.low_stock' => 'مخزون منخفض',
    'lowstock.threshold' => 'الحد الأدنى',
    'lowstock.warehouse' => 'المستودع',
    'lowstock.product' => 'المنتج',
    'lowstock.on_hand' => 'متوفر',
    'lowstock.reserved' => 'محجوز',
    'lowstock.available' => 'متاح',

    /* =========================
     * Contacts
     * ========================= */
    'contacts.contacts' => 'جهات الاتصال',
    'contacts.search_placeholder' => 'البحث بالاسم/البريد الإلكتروني/الهاتف',
    'contacts.all_customers' => 'جميع العملاء',
    'contacts.new_contact' => '+ جهة اتصال جديدة',
    'contacts.title' => 'المسمى الوظيفي',
    'contacts.client' => 'العميل',

    /* =========================
     * Additional Reports
     * ========================= */
    'reports.purchasing_report' => 'تقرير المشتريات',
    'reports.from' => 'من',
    'reports.to' => 'إلى',
    'reports.total_purchases' => 'إجمالي المشتريات',
    'reports.total_purchase_returns' => 'إجمالي مرتجعات المشتريات',
    'reports.net_purchases' => 'صافي المشتريات',
    'reports.supplier_payments' => 'مدفوعات الموردين',
    'reports.date' => 'التاريخ',
    'reports.type' => 'النوع',
    'reports.reference' => 'المرجع',
    'reports.supplier' => 'المورد',
    'reports.debit' => 'مدين',
    'reports.credit' => 'دائن',
    'reports.inventory_valuation' => 'تقييم المخزون (متوسط مرجح)',
    'reports.warehouse' => 'المستودع',
    'reports.product' => 'المنتج',
    'reports.on_hand' => 'متوفر',
    'reports.avg_cost' => 'متوسط التكلفة',
    'reports.value' => 'القيمة',
    'reports.ap_aging' => 'تقرير أعمار الحسابات الدائنة',
    'reports.as_of' => 'اعتباراً من',
    'reports.0_30_days' => '0–30',
    'reports.31_60_days' => '31–60',
    'reports.61_90_days' => '61–90',
    'reports.90_plus_days' => '90+',

    /* =========================
     * Settings
     * ========================= */
    'settings.taxes_currency_settings' => 'إعدادات الضرائب والعملات',
    'settings.manage_tax_rates' => 'إدارة معدلات الضرائب',
    'settings.manage_currencies' => 'إدارة العملات',
    'settings.back_to_settings' => 'العودة للإعدادات',
    'settings.tax_rate_management' => 'إدارة معدلات الضرائب',
    'settings.add_tax_rate' => 'إضافة معدل ضريبة',
    'settings.currency_management' => 'إدارة العملات',
    'settings.add_currency' => 'إضافة عملة',
    'settings.units_sequences' => 'الوحدات والتسلسلات',
    'settings.units_sequences_description' => 'هذه صفحة إعدادات مؤقتة. حدد وحدات القياس وتسلسلات ترقيم المستندات هنا (مثل INV، PO، PI).',
    'settings.company_information' => 'معلومات الشركة',
    'settings.company_name' => 'اسم الشركة',
    'settings.company_name_placeholder' => 'اسم شركتك',
    'settings.address' => 'العنوان',
    'settings.company_address_placeholder' => 'عنوان الشركة للفواتير',
    'settings.phone_placeholder' => '+20 xxx xxx xxxx',
    'settings.email_placeholder' => 'info@company.com',
    'settings.currency_settings' => 'إعدادات العملة',
    'settings.base_currency' => 'العملة الأساسية',
    'settings.currency_symbol' => 'رمز العملة',
    'settings.symbol_position' => 'موضع الرمز',
    'settings.before_symbol' => 'قبل ($ 100.00)',
    'settings.after_symbol' => 'بعد (100.00 EGP)',
    'settings.decimal_places' => 'الخانات العشرية',
    'settings.tax_settings' => 'إعدادات الضرائب',
    'settings.default_tax_rate' => 'معدل الضريبة الافتراضي (%)',
    'settings.tax_calculation_method' => 'طريقة حساب الضريبة',
    'settings.tax_exclusive' => 'ضريبة منفصلة (الضريبة مضافة للمبلغ)',
    'settings.tax_inclusive' => 'ضريبة شاملة (الضريبة مشمولة في المبلغ)',
    'settings.current_tax_rates' => 'معدلات الضرائب الحالية',
    'settings.no_tax_rates_configured' => 'لم يتم تكوين معدلات ضرائب',
    'settings.default' => 'افتراضي',
    'settings.active_currencies' => 'العملات النشطة',
    'settings.no_currencies_configured' => 'لم يتم تكوين عملات',
    'settings.code' => 'الرمز',
    'settings.symbol' => 'الرمز',
    'settings.exchange_rate' => 'سعر الصرف',
    'settings.base' => 'أساسي',
    'settings.save_settings' => 'حفظ الإعدادات',

    /* =========================
     * Translation Management
     * ========================= */
    'translations.translation_management' => 'إدارة الترجمة',
    'translations.translation_stats' => 'إحصائيات الترجمة',

    /* =========================
     * Notifications
     * ========================= */
    'notifications.notifications' => 'الإشعارات',
    'notifications.notifications_description' => 'هذا مؤقت لتكوين إشعارات النظام وتكاملات البريد الإلكتروني/webhook.',
    'notifications.manage_system_notifications' => 'إدارة إشعارات وتنبيهات النظام',
    'notifications.create_notification' => 'إنشاء إشعار',
    'notifications.edit_notification' => 'تعديل الإشعار',
    'notifications.create_notification_description' => 'إنشاء إشعار نظام جديد',
    'notifications.edit_notification_description' => 'تعديل إشعار نظام موجود',
    'notifications.templates' => 'القوالب',
    'notifications.notification_templates' => 'قوالب الإشعارات',
    'notifications.manage_notification_templates' => 'إدارة قوالب الإشعارات والإعدادات المسبقة',
    
    // Statistics
    'notifications.total_notifications' => 'إجمالي الإشعارات',
    'notifications.unread_notifications' => 'الإشعارات غير المقروءة',
    'notifications.active_notifications' => 'الإشعارات النشطة',
    'notifications.scheduled_notifications' => 'الإشعارات المجدولة',
    
    // Table headers
    'notifications.all_notifications' => 'جميع الإشعارات',
    'notifications.title' => 'العنوان',
    'notifications.type' => 'النوع',
    'notifications.channel' => 'القناة',
    'notifications.target' => 'الهدف',
    'notifications.status' => 'الحالة',
    'notifications.created_by' => 'أنشأ بواسطة',
    'notifications.created_at' => 'تاريخ الإنشاء',
    
    // Status badges
    'notifications.unread' => 'غير مقروء',
    'notifications.active' => 'نشط',
    'notifications.inactive' => 'غير نشط',
    'notifications.all_users' => 'جميع المستخدمين',
    
    // Notification types
    'notifications.type_info' => 'معلومات',
    'notifications.type_success' => 'نجح',
    'notifications.type_warning' => 'تحذير',
    'notifications.type_error' => 'خطأ',
    
    // Channels
    'notifications.channel_in_app' => 'داخل التطبيق',
    'notifications.channel_email' => 'البريد الإلكتروني',
    'notifications.channel_sms' => 'رسالة نصية',
    'notifications.channel_webhook' => 'Webhook',
    
    // Target types
    'notifications.target_type_user' => 'مستخدم',
    'notifications.target_type_role' => 'دور',
    'notifications.target_type_group' => 'مجموعة',
    
    // Actions
    'notifications.mark_as_read' => 'تعيين كمقروء',
    'notifications.confirm_delete' => 'هل أنت متأكد من حذف هذا الإشعار؟',
    
    // Empty states
    'notifications.no_notifications' => 'لا توجد إشعارات',
    'notifications.no_notifications_description' => 'لم يتم إنشاء أي إشعارات بعد.',
    'notifications.create_first_notification' => 'إنشاء أول إشعار',
    
    // Form fields
    'notifications.title_placeholder' => 'أدخل عنوان الإشعار',
    'notifications.message' => 'الرسالة',
    'notifications.message_placeholder' => 'أدخل رسالة الإشعار',
    'notifications.select_type' => 'اختر النوع',
    'notifications.select_channel' => 'اختر القناة',
    'notifications.select_target_type' => 'اختر نوع الهدف',
    'notifications.target_type' => 'نوع الهدف',
    'notifications.target_user' => 'المستخدم المستهدف',
    'notifications.select_user' => 'اختر المستخدم',
    'notifications.scheduled_at' => 'مجدول في',
    'notifications.scheduled_at_help' => 'اترك فارغاً للإرسال فوراً',
    'notifications.is_active' => 'نشط',
    
    // Help section
    'notifications.help' => 'المساعدة',
    'notifications.notification_types' => 'أنواع الإشعارات',
    'notifications.type_info_description' => 'رسائل المعلومات العامة',
    'notifications.type_success_description' => 'رسائل النجاح والتأكيد',
    'notifications.type_warning_description' => 'رسائل التحذير والحذر',
    'notifications.type_error_description' => 'رسائل الخطأ والفشل',
    'notifications.channels' => 'القنوات',
    'notifications.channel_in_app_description' => 'معروضة داخل التطبيق',
    'notifications.channel_email_description' => 'مرسلة عبر البريد الإلكتروني',
    'notifications.channel_sms_description' => 'مرسلة عبر الرسائل النصية',
    'notifications.channel_webhook_description' => 'مرسلة عبر webhook',
    'notifications.targeting' => 'التوجيه',
    'notifications.target_all_description' => 'إرسال لجميع المستخدمين',
    'notifications.target_user_description' => 'إرسال لمستخدم محدد',
    'notifications.target_role_description' => 'إرسال للمستخدمين بدور محدد',
    'notifications.target_group_description' => 'إرسال للمستخدمين في مجموعة محددة',
    
    // Templates
    'notifications.no_templates' => 'لا توجد قوالب',
    'notifications.no_templates_description' => 'لا توجد قوالب إشعارات متاحة.',
    'notifications.title_template' => 'قالب العنوان',
    'notifications.message_template' => 'قالب الرسالة',
    'notifications.available_variables' => 'المتغيرات المتاحة',
    'notifications.created' => 'تم الإنشاء',
    'notifications.use_template' => 'استخدام القالب',
    'notifications.preview' => 'معاينة',
    'notifications.template_preview' => 'معاينة القالب',
    'notifications.template_preview_note' => 'معاينة القالب',
    'notifications.template_preview_description' => 'هذه معاينة لكيفية ظهور القالب مع بيانات تجريبية.',
    'notifications.preview_title' => 'معاينة العنوان',
    'notifications.preview_message' => 'معاينة الرسالة',
    'notifications.use_this_template' => 'استخدام هذا القالب',

    /* =========================
     * Integrations
     * ========================= */
    'integrations.integrations' => 'التكاملات',
    'integrations.integrations_description' => 'هذا مؤقت للتكاملات الخارجية (مثل المحاسبة، CRM، بوابات الدفع). قريباً.',

    'common.id'                => 'المعرف',
    'common.all'               => 'الكل',
    'common.none'              => 'لا شيء',
    'common.loading'           => 'جاري التحميل...',
    'common.no_data'           => 'لا توجد بيانات متاحة',
    'common.error'             => 'خطأ',
    'common.success'           => 'نجح',
    'common.warning'           => 'تحذير',
    'common.info'              => 'معلومات',
];
