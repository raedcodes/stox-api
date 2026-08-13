<?php

return [

    'trend' => [
        'same_as' => 'كما كان :label',
        'up_from_zero' => 'ارتفاع من الصفر :label',
        'vs' => ':percent% :direction مقارنة بـ :label',
        'up' => 'ارتفاع',
        'down' => 'انخفاض',
        'yesterday' => 'الأمس',
        'last_month' => 'الشهر الماضي',
    ],

    'kpis' => [
        'todays_sales' => 'مبيعات اليوم',
        'todays_revenue' => 'إيراد اليوم',
        'low_out_of_stock' => 'منخفض / نافذ من المخزون',
        'sales_this_month' => 'مبيعات هذا الشهر',
        'revenue_this_month' => 'إيراد هذا الشهر',
        'gross_profit_this_month' => 'إجمالي الربح هذا الشهر',
        'margin' => 'هامش :percent%',
        'accounts_receivable' => 'الذمم المدينة',
        'owed_by_customers' => 'مستحق من العملاء',
        'accounts_payable' => 'الذمم الدائنة',
        'owed_to_suppliers' => 'مستحق للموردين',
        'inventory_value' => 'قيمة المخزون',
        'inventory_value_description' => 'المخزون الحالي، بسعر التكلفة',
    ],

    'widgets' => [
        'low_stock_heading' => 'منتجات منخفضة أو نافذة من المخزون',
        'expiry_alerts_heading' => 'دفعات منتهية وقريبة الانتهاء',
        'recent_credit_sales_heading' => 'مبيعات دين حديثة',
        'top_products_heading' => 'أفضل المنتجات (آخر 30 يوماً)',
        'revenue_trend_heading' => 'الإيراد مقابل تكلفة البضاعة المباعة (آخر 30 يوماً)',
        'top_products_chart_heading' => 'أفضل المنتجات من حيث الإيراد (آخر 30 يوماً)',
        'stock_expiry_timeline_heading' => 'الجدول الزمني لانتهاء المخزون',
    ],

    'columns' => [
        'on_hand' => 'المتوفر',
        'units_sold' => 'الوحدات المباعة',
        'gross_revenue' => 'الإيراد الإجمالي',
        'customer_owes_total' => 'إجمالي مستحق العميل',
        'amount' => 'المبلغ',
    ],

    'actions' => [
        'create_po' => 'إنشاء أمر شراء',
    ],

    'chart' => [
        'revenue' => 'الإيراد',
        'cost_of_goods_sold' => 'تكلفة البضاعة المباعة',
        'quantity' => 'الكمية',
        'already_expired' => 'منتهية بالفعل',
        'within_30_days' => '0–30 يوماً',
        'within_60_days' => '31–60 يوماً',
        'within_90_days' => '61–90 يوماً',
        'beyond_90_days' => '+90 يوماً',
    ],

    'working_from' => 'العمل من',
    'all_locations' => 'كل المواقع',
    'location_helper' => 'يحدد نطاق هذه اللوحة ويُستخدم كافتراضي في نقطة البيع وأوامر الشراء والتحويلات — يمكن تغييره في كل نموذج.',

];
