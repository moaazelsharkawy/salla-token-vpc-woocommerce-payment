function st_unified_withdrawal_shortcode() {
    if (!is_user_logged_in()) {
        return '<p style="text-align: center;">يرجى <a href="' . wp_login_url() . '">تسجيل الدخول</a> لاستخدام المحفظة الموحدة.</p>';
    }

    $user_id = get_current_user_id();
    
    // استخدام النظام الجديد - جلب كل الأرصدة في استعلام واحد
    $balances = st_get_all_balances($user_id);
    $st_balance = $balances['st'];
    $usdt_balance = $balances['usdt'];
    $sol_balance = $balances['sol'];
    
    // جلب قيم الإعدادات
    $usdt_withdrawal_fee = (float) get_option('st_usdt_withdrawal_fee_amount', 1.00);
    $usdt_min_withdraw = (float) get_option('st_usdt_min_withdraw_amount', 1.01);
    $st_min_withdraw = (float) get_option('st_min_withdraw_amount', 1.0);
    $st_extra_fee_amount = (float) get_option('st_extra_withdrawal_fee_amount', 0.1);
    $sol_min_withdraw = (float) get_option('st_sol_min_withdraw_amount', 0.001);
    $sol_withdrawal_fee = (float) get_option('st_sol_withdrawal_fee_amount', 0.00005);
    
    ob_start();
    ?>
    <style>
    /* إضافة تنسيقات جديدة لزر تعبئة كل المبلغ */
    .withdrawal-amount-container {
        position: relative;
    }
    .fill-balance-btn {
        position: absolute;
        left: 5px; /* أو right: 5px; حسب اتجاه الموقع */
        top: 50%;
        transform: translateY(-50%);
        background: #e0e0e0;
        border: none;
        padding: 5px 10px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.2s ease;
        z-index: 2;
    }
    .fill-balance-btn:hover {
        background: #ccc;
    }
    .fill-balance-btn:active {
        transform: translateY(-50%) scale(0.95);
    }
    /* Unified Withdrawal Styles */
    .unified-withdrawal-container {
        direction: rtl;
        text-align: right;
        padding: 30px;
        background: #f8f5ff;
        border-radius: 16px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        max-width: auto;
        margin: 24px auto;
        font-family: 'Cairo', sans-serif;
        box-sizing: border-box;
    }
    
    
    .unified-withdrawal-container h3 {
        color: #7c4dff;
        text-align: center;
        font-size: 24px;
        margin-bottom: 20px;
    }
    .balance-cards-container {
        display: flex;
        justify-content: space-between;
        gap: 15px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }
    .balance-card {
        flex: 1 1 30%;
        min-width: 140px;
        background: linear-gradient(135deg, #7c4dff, #9c27b0);
        color: #fff;
        padding: 18px;
        border-radius: 12px;
        text-align: center;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }
    .balance-card h4 {
        color: #fff;
        font-size: 18px;
        margin: 0 0 8px 0;
    }
    .balance-card .balance-value {
        font-size: 24px;
        font-weight: bold;
        display: block;
        margin-top: 5px;
    }
    .form-section {
        background: #fff;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        box-sizing: border-box;
    }
    .form-section label {
        font-weight: bold;
        color: #4a148c;
        display: block;
        margin-bottom: 10px;
    }
    .input-group-withdrawal {
        margin-bottom: 18px;
    }
    .form-section input, .form-section select {
        width: 100%;
        padding: 14px;
        border-radius: 10px;
        border: 2px solid #e1bee7;
        background-color: #fff;
        transition: border-color 0.3s ease;
        margin-bottom: 8px;
        box-sizing: border-box;
        font-size: 15px;
    }
    .form-section input:focus, .form-section select:focus {
        border-color: #7c4dff;
        outline: none;
    }
    .form-section .withdrawal-conditions {
        background-color: #f8f5ff;
        padding: 15px;
        border-radius: 8px;
        border-right: 4px solid #7c4dff;
        margin-bottom: 16px;
    }
    .form-section .withdrawal-conditions p {
        margin: 0;
        font-size: 14px;
        color: #555;
        line-height: 1.6;
    }
    .withdrawal-button {
        width: 100%;
        padding: 15px;
        background: linear-gradient(135deg, #7c4dff, #9c27b0);
        color: white;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        font-weight: bold;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        transition: transform 0.18s ease, box-shadow 0.18s ease;
        font-size: 16px;
    }
    .withdrawal-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(124, 77, 255, 0.36);
    }
    .withdrawal-button:disabled {
        background: #ccc;
        cursor: not-allowed;
    }
    #fee-order-group {
        display: none;
    }
    @media (max-width: 992px) {
        .unified-withdrawal-container { padding: 22px; max-width: 680px; }
        .balance-card .balance-value { font-size: 22px; }
        .form-section { padding: 20px; }
        .form-section input, .form-section select { padding: 12px; font-size: 14px; }
        .withdrawal-button { padding: 13px; font-size: 15px; }
    }
    @media (max-width: 768px) {
        .unified-withdrawal-container { padding: 108px; margin: 12px; border-radius: 12px; }
        .balance-cards-container { flex-direction: column; gap: 12px; }
        .balance-card { flex: 1 1 100%; padding: 14px; border-radius: 10px; flex-direction: row; justify-content: space-between; align-items: center; }
        .balance-card h4 { font-size: 15px; margin: 0; text-align: left; }
        .balance-card .balance-value { font-size: 20px; margin-top: 0; }
        .form-section { padding: 16px; }
        .form-section label { font-size: 14px; }
        .form-section input, .form-section select { padding: 12px; font-size: 14px; }
        .withdrawal-button { padding: 12px; font-size: 15px; }
        .swal2-html-container { word-break: break-word; white-space: normal; }
    }
    @media (max-width: 420px) {
        .unified-withdrawal-container h3 { font-size: 20px; }
        .balance-card .balance-value { font-size: 18px; }
        .form-section input, .form-section select { padding: 10px; font-size: 13px; }
        .withdrawal-button { font-size: 14px; padding: 11px; margin-top: 20px;}
    }
    .st-withdrawal-gtranslate-container { position: initial; margin: 20px auto; text-align: right; border-radius: 5px; transform: translate(15px, -10px); }
    @media (max-width: 768px) {
        .st-withdrawal-gtranslate-container { position: relative; margin: 10px auto; text-align: right; transform: translate(12px, -100px); }
        .st-withdrawal-container h3 { padding-top: 30px !important; }
        .st-main-message-block h3 { font-size: 20px !important; line-height: 30px !important; margin-top: -30px;}
    }
    .fee-info-block { background: #e1f5fe; padding: 20px; border-radius: 12px; margin-bottom: 25px; border: 1px solid #b3e5fc; color: #01579b; font-size: 15px; line-height: 1.6; text-align: right; display: flex; align-items: flex-start; gap: 15px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); }
    .fee-info-block strong { color: #0277bd; font-weight: bold; }
    .fee-info-block .alert-icon { color: #0277bd; font-size: 24px; flex-shrink: 0; }
    .fee-info-block .text-content { flex-grow: 1; }
    .fee-info-block p { margin: 0; }
    .buttons-row.full-width-button { display: block; text-align: center; }
    .buttons-row.full-width-button .withdraw-button[type="submit"] { width: 100%; }
    .st-current-balance-card { background: #f8f5ff; padding: 20px; border-radius: 12px; margin: 0 auto 25px 0; width: auto; box-sizing: border-box; text-align: center; }
    .st-current-balance-card strong { color: #7c4dff; font-size: 18px; }
    #current-balance-withdrawal { display: block; margin-top: 10px; font-size: 24px; font-weight: 700; color: #4a148c; }
    .input-group-withdrawal { position: relative; margin-bottom: 25px; }
    .input-group-withdrawal i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #7c4dff; font-size: 20px; }
    .st-withdrawal-container input { padding: 14px 14px 14px 45px; font-size: 16px; border: 2px solid #e1bee7; border-radius: 10px; background: #f8f5ff; color: #4a148c; transition: all 0.3s ease; }
    .st-withdrawal-container input:focus { border-color: #7c4dff; box-shadow: 0 4px 12px rgba(124,77,255,0.2); background: #fff; }
    .buttons-row { display: flex; justify-content: center; gap: 15px; margin-top: 15px; }
    .withdraw-button { padding: 10px 18px; font-size: 14px; border: none; border-radius: 8px; background: #7c4dff; color: #fff; cursor: pointer; transition: all 0.3s ease; }
    .withdraw-button:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(124,77,255,0.4); }
    .withdraw-button:active { transform: translateY(0); }
    .help-icon-withdrawal { position: relative; top: 10px; right: -7px; width: 40px; height: 40px; background: linear-gradient(135deg, #7c4dff, #9c27b0); border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(124,77,255,0.2); z-index: 10; }
    .help-icon-withdrawal i { color: #fff; font-size: 20px; }
    .help-icon-withdrawal:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(124,77,255,0.4); }
    .pending-requests-container { max-width: 600px; margin: 20px auto; padding: 20px; background: #fff; border-radius: 16px; box-shadow: 0 8px 32px rgba(0,0,0,0.05); }
    .pending-requests-container h4 { color: #7c4dff; text-align: center; margin-bottom: 15px; font-size: 20px; border-bottom: 2px solid #f3e5f5; padding-bottom: 10px; margin-bottom: 50px; }
    .requests-grid { display: grid; gap: 20px; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); }
    @media (max-width: 768px) {
        .requests-grid { justify-content: center; grid-template-columns: 1fr; }
    }
    .request-card { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); transition: transform 0.3s ease, box-shadow 0.3s ease; border-right: 5px solid; }
    .request-card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
    .request-card.status-pending { border-color: #ffc107; background-color: #fff8e9; }
    .request-card.status-completed { border-color: #4caf50; background-color: #e9fff0; }
    .request-card.status-failed { border-color: #f44336; background-color: #ffeded; }
    .request-card-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px dashed rgba(0,0,0,0.1); padding-bottom: 10px; margin-bottom: 15px; }
    .request-id { font-weight: bold; font-size: 1.1em; color: #5d3fd3; }
    .request-status { font-weight: bold; color: #444; }
    .request-status i { margin-left: 8px; font-size: 1.2em; }
    .request-card-body { display: flex; flex-direction: column; gap: 10px; }
    .detail-row { display: flex; justify-content: space-between; align-items: center; font-size: 0.9em; }
    .detail-row .label { color: #777; font-weight: normal; }
    .detail-row .value { color: #333; font-weight: 500; }
    .address-value { font-family: monospace; font-size: 0.8em; direction: ltr; text-align: left; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 150px; }
    .solscan-link { color: #5d3fd3; text-decoration: none; font-weight: bold; }
    .solscan-link:hover { text-decoration: underline; }
    .pagination-links button { text-decoration: none; }
    .st-main-message-block { background: linear-gradient(135deg, #7c4dff, #9c27b0); color: #fff; text-align: center; padding: 25px; border-radius: 16px; margin-bottom: 20px; box-shadow: 0 8px 32px rgba(0,0,0,0.2); }
    .st-main-message-block h3 { color: #fff; font-size: 26px; font-weight: bold; margin: 0 0 15px 0; border-bottom: none; padding-bottom: 0; }
    .st-main-message-block h3 i { margin-left: 10px; font-size: 30px; }
    .st-instructions-button { background-color: #fff; color: #7c4dff; border: none; padding: 10px 20px; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; transition: transform 0.3s ease, box-shadow 0.3s ease; }
    .st-instructions-button:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
    
    .st-instructions-content { text-align: right; direction: rtl; font-size: 15px; line-height: 1.8; }
    .st-instructions-content ul { list-style: none; padding: 0; margin: 0; }
    .st-instructions-content li { background: #f8f5ff; padding: 12px; margin-bottom: 10px; border-radius: 8px; box-shadow: inset 0 0 5px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 15px; }
    .st-instructions-content li:last-child { margin-bottom: 0; }
    .st-instructions-content li i { color: #7c4dff; font-size: 20px; flex-shrink: 0; }
    .swal2-instructions-container { max-height: 50vh !important; overflow-y: auto !important; }
    .swal-actions-row { flex-direction: row-reverse; }
    .swal-actions-row button.stus-swal-button { flex-grow: 1; }
    .sol-fee-modal.swal2-popup { width: 95% !important; max-width: 600px !important; border-radius: 20px !important; padding: 2rem !important; background: linear-gradient(145deg, #ffffff, #f0f2f5); box-shadow: 0 15px 40px rgba(0,0,0,0.1); direction: rtl; text-align: right; max-height: 80vh; overflow-y: auto; overflow-x: hidden; }
    .sol-fee-modal .swal2-title { color: #4a148c !important; font-size: 28px !important; font-weight: 700 !important; border-bottom: 3px solid #7c4dff; padding-bottom: 15px; margin-bottom: 25px; }
    .sol-fee-modal .swal2-icon.swal2-info { border-color: #7c4dff !important; color: #7c4dff !important; }
    .sol-fee-modal-content p { color: #555; font-size: 16px; line-height: 1.8; }
    .sol-fee-details { background: #f8f5ff; padding: 20px; border-radius: 15px; margin: 30px 0; border: 2px dashed #e1bee7; }
    .sol-fee-details .detail-item { font-size: 17px; font-weight: 600; color: #333; margin-bottom: 12px; }
    .sol-fee-details .detail-item i { color: #7c4dff; margin-left: 12px; }
    .sol-amount { color: #d32f2f; font-weight: bold; font-size: 20px; }
    .sol-address-container { display: flex; align-items: center; justify-content: center; margin-top: 10px; }
    .sol-address { background-color: #e9eef2; padding: 8px 15px; border-radius: 8px; font-family: 'monospace'; font-size: 14px; color: #333; word-break: break-all; direction: ltr; user-select: all; overflow-x: hidden; white-space: normal; cursor: pointer; }
    .swal2-input.custom-input { width: 80% !important; max-width: 400px; margin: 0 auto !important; }
    .swal-verify-button { background-color: #7c4dff; color: #fff; border: none; padding: 15px 30px; border-radius: 10px; font-size: 18px; font-weight: bold; cursor: pointer; transition: transform 0.3s ease, box-shadow 0.3s ease; width: 100%; margin-top: 20px; }
    .swal-verify-button:hover:not(:disabled) { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(124, 77, 255, 0.4); }
    .swal-verify-button:disabled { opacity: 0.6; cursor: not-allowed; }
    .swal2-confirm.st-swal-button { background-color: #7c4dff !important; border-radius: 8px !important; padding: 12px 25px !important; font-size: 16px !important; font-weight: bold !important; }
    @media (max-width: 480px) {
        .sol-fee-modal.swal2-popup { padding: 1rem !important; max-height: 75vh; }
        .sol-fee-modal .swal2-title { font-size: 22px !important; }
        .sol-fee-modal-content p, .sol-fee-details .detail-item { font-size: 14px; }
        .sol-amount { font-size: 18px; }
        .sol-address { font-size: 12px; }
    }
    .last-request-details { background-color: #f0f0f0; padding: 15px; border-radius: 12px; margin-bottom: 20px; border: 1px dashed #ccc; text-align: right; line-height: 1.6; }
    .last-request-details p { margin: 0; font-size: 14px; color: #555; }
    .last-request-details strong { color: #333; }
    .last-order-id { font-family: monospace; direction: ltr; text-align: left; background-color: #e9eef2; padding: 4px 8px; border-radius: 4px; cursor: pointer; }
    .last-order-id:hover { background-color: #d1d9e0; }
    .last-order-status { font-weight: bold; }
    
/* Styles for the new withdrawal conditions cards */
.withdrawal-conditions-card {
    background: #ffffff;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    border-right: 5px solid;
    margin-top: 25px;
}
.withdrawal-conditions-card.st-card {
    border-color: #7c4dff; /* لون أرجواني لـ ST */
}
.withdrawal-conditions-card.usdt-card {
    border-color: #2e7d32; /* لون أخضر لـ USDT */
}
.withdrawal-conditions-card.sol-card {
    border-color: #ff9800; /* لون برتقالي لـ SOL */
}
.withdrawal-conditions-card h4 {
    color: #4a148c;
    font-size: 18px;
    font-weight: bold;
    margin-top: 0;
    margin-bottom: 15px;
    border-bottom: 2px solid #eee;
    padding-bottom: 10px;
}
.withdrawal-conditions-card ul {
    list-style: none;
    padding: 0;
    margin: 0;
}
.withdrawal-conditions-card li {
    font-size: 15px;
    color: #555;
    line-height: 1.6;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 12px;
}
.withdrawal-conditions-card li:last-child {
    margin-bottom: 0;
}
.withdrawal-conditions-card li i {
    color: #7c4dff;
    font-size: 20px;
    flex-shrink: 0;
}

/* Styles for the new confirmation pop-up */
.stus-swal-popup {
    direction: rtl !important;
    text-align: right !important;
    background: #ffffff !important; /* خلفية بيضاء نظيفة */
    border-radius: 16px !important; /* زوايا أكثر نعومة */
    padding: 0 !important; /* إزالة الهامش الداخلي لإتاحة التحكم الكامل */
    box-shadow: 0 15px 50px rgba(0, 0, 0, 0.1) !important; /* ظل أكثر نعومة */
    width: 90% !important;
    max-width: 480px !important; /* تحديد عرض أقصى */
    border-top: 5px solid #ffc107; /* شريط علوي بلون التحذير */
    overflow: hidden !important;
}

/* تصميم العنوان */
.stus-swal-title {
    color: #1a202c !important; /* لون نص أغمق وأكثر وضوحًا */
    font-size: 24px !important;
    font-weight: 700 !important;
    margin: 0 !important;
    padding: 1.5rem 2rem !important; /* هوامش داخلية للعنوان */
    border-bottom: 1px solid #edf2f7; /* خط فاصل ناعم */
}

/* تصميم أيقونة التحذير */
.stus-swal-icon.swal2-warning {
    color: #ffc107 !important; /* ليتناسب مع الشريط العلوي */
    border-color: #ffc107 !important;
    margin: 1.5rem auto 0.5rem auto !important; /* تعديل الهوامش */
    width: 60px !important;
    height: 60px !important;
}

/* منطقة محتوى HTML */
.stus-swal-html {
    font-size: 16px !important;
    color: #4a5568 !important; /* لون نص أكثر راحة للعين */
    line-height: 1.7 !important;
    margin: 0 !important;
    padding: 0 2rem 1.5rem 2rem !important; /* هوامش داخلية للمحتوى */
}

.stus-swal-html strong {
    color: #2d3748 !important; /* جعل النص العريض أكثر بروزًا */
    font-weight: 600;
}

.stus-swal-html p {
    margin: 0.75rem 0;
}

/* حاوية الأزرار */
.stus-swal-actions {
    margin: 0 !important;
    width: 100% !important;
    gap: 1rem !important;
    background-color: #f7fafc !important; /* خلفية مميزة لمنطقة الأزرار */
    padding: 1.5rem 2rem !important;
    border-top: 1px solid #edf2f7;
}

/* تصميم الأزرار بشكل عام */
.stus-swal-button {
    border-radius: 8px !important;
    padding: 12px 28px !important;
    font-size: 16px !important;
    font-weight: 600 !important;
    transition: all 0.2s ease !important;
    border: none !important;
    flex-grow: 1;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

/* تصميم زر التأكيد (الإجراء الأساسي) */
button.swal2-confirm.stus-swal-button {
    background-color: #3182ce !important; /* لون أزرق احترافي */
    color: white !important;
}

button.swal2-confirm.stus-swal-button:hover {
    background-color: #2b6cb0 !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(49, 130, 206, 0.2);
}

/* تصميم زر الإلغاء (الإجراء الثانوي) */
button.swal2-cancel.stus-swal-button {
    background-color: #e2e8f0 !important;
    color: #4a5568 !important;
}

button.swal2-cancel.stus-swal-button:hover {
    background-color: #cbd5e0 !important;
}

/* تعديلات للشاشات الصغيرة */
@media (max-width: 480px) {
    .stus-swal-title,
    .stus-swal-html,
    .stus-swal-actions {
        padding-left: 1.5rem !important;
        padding-right: 1.5rem !important;
    }

    .stus-swal-title {
        font-size: 20px !important;
    }

    .stus-swal-html {
        font-size: 15px !important;
    }
    
    .stus-swal-actions {
        flex-direction: column-reverse; /* ترتيب الأزرار فوق بعضها */
    }

    .stus-swal-button {
        width: 100%;
    }
}
.confirmation-container {
    max-height: 250px;
    overflow-y: auto;
    text-align: right;
    direction: rtl;
    padding: 8px;
    line-height: 1.4;
        overflow-x: hidden;
}

.info-item {
    margin: 8px 0;
}

.info-item strong {
    color: #333; /* A strong color for labels */
}

.address-value {
    word-break: break-all;
    font-size: 11px;
    font-family: monospace; /* Use a monospace font for the address */
}

.warning-message {
    color: red;
    font-weight: bold;
    margin: 10px 0;
    font-size: 12px;
}

.address-value {
    /* قواعد الكسر الجديدة */
    display: block;
    word-break: break-all;
    word-wrap: break-word; /* إضافة لدعم أفضل */
    overflow-wrap: break-word; /* إضافة أخرى لضمان التوافق */

    /* قواعد التصميم الموجودة */
    font-size: 11px;
    font-family: monospace;
}


.stus-swal-popup .swal2-actions {
    display: flex !important;
    flex-direction: column-reverse;
    align-items: center;
    gap: 1rem !important;
    margin-top: -30px;
    /* Other styles you've added */
}

.stus-swal-popup .swal2-confirm {
    width: 100%;
}

.stus-swal-popup .swal2-cancel {
    width: 100%;
}

.st-success-popup {
    direction: rtl !important;
    text-align: right !important;
    background: linear-gradient(135deg, #f0e9ff, #f8f5ff) !important;
    border-radius: 20px !important;
    padding: 2.5rem !important;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15) !important;
    width: 90% !important;
    max-width: 500px !important; /* تم زيادة العرض الأقصى هنا */
}

.st-success-popup .swal2-title {
    color: #4a148c !important;
    font-size: 24px !important;
    font-weight: 700 !important;
    margin-bottom: 1rem !important;
}

.st-success-popup .swal2-icon.swal2-success {
    border-color: #4caf50 !important;
    color: #4caf50 !important;
}

.st-success-popup .swal2-html-container {
    max-height: 250px;
    overflow-y: auto;
    word-break: normal; /* تم تغيير هذه الخاصية */
    white-space: normal;
}
    </style>

    <div class="st-main-message-block">
        <div class="st-main-message-content">
            <div class="st-withdrawal-gtranslate-container">
            <?php echo do_shortcode('[gtranslate]'); ?>
        </div>
            <h3>
                <i class="fa-solid fa-hand-holding-dollar"></i> مرحبا بك في تطبيق السحب على بلوكتشين سولانا
            </h3>
            <button id="st-show-instructions" class="st-instructions-button">تعليمات استخدام التطبيق</button>
        </div>
    </div>
    <div class="unified-withdrawal-container">
        <h3><i class="fas fa-wallet"></i> تطبيق السحب </h3>
        <div class="balance-cards-container">
            <div class="balance-card st-balance-card">
                <h4>رصيد ST</h4>
                <span class="balance-value"><?php echo number_format($st_balance, 5); ?> ST</span>
            </div>
    
    <div class="balance-card usdt-balance-card">
    <h4>رصيد USDT</h4>
    <span class="balance-value"><?php echo number_format($usdt_balance, 5); ?> USDT</span>
    <div class="swap-icon-container">
        <i class="fas fa-exchange-alt swap-icon" onclick="showUsdtToSolSwap()" title="تحويل إلى SOL"></i>
        <span class="swap-label"> تحتاج SOL ؟</span>
    </div>
</div>



            <div class="balance-card sol-balance-card">
                <h4>رصيد SOL</h4>
                <span class="balance-value"><?php echo number_format($sol_balance, 5); ?> SOL</span>
            </div>
        </div>

        <form id="unified-withdrawal-form" class="form-section">
<label for="currency_select">اختر العملة:</label>
<div class="currency-select-container">
    <select name="currency_select" id="currency_select">
        <option value="st">ST</option>
        <option value="usdt">USDT</option>
        <option value="sol">SOL</option>
    </select>
</div>
            <div class="input-group-withdrawal withdrawal-amount-container">
                <label for="withdraw_amount">المبلغ المراد سحبه:</label>
                <input type="number" name="withdraw_amount" id="withdraw_amount" placeholder="أدخل المبلغ هنا" required min="0.000001" step="0.000001" style="font-family: 'Cairo', sans-serif;" />
                <button type="button" class="fill-balance-btn">أقصى رصيد</button>
            </div>

            <div class="input-group-withdrawal">
                <label for="withdraw_address">عنوان المحفظة (شبكة Solana):</label>
                <input type="text" name="withdraw_address" id="withdraw_address" placeholder="أدخل عنوان المحفظة هنا" required pattern="^[1-9A-HJ-NP-Za-km-z]{32,44}$" />
            </div>

            <div id="conditional-fields-container">
            </div>
            
            <?php wp_nonce_field('process_withdrawal_nonce_action', 'process_withdrawal_nonce_field'); ?>
            <button type="button" id="unified-withdrawal-submit" class="withdrawal-button">
        <span class="button-text"><i class="fas fa-paper-plane"></i> تقديم طلب السحب</span>
    <span class="spinner" aria-hidden="true" style="display: none;"><i class="fas fa-spinner fa-spin"></i> جاري الإرسال...</span>
</button>

        </form>
        <div id="withdrawal-response"></div>
    </div>
    
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
<script>
    jQuery(document).ready(function($) {
        
        
        const $form = $('#unified-withdrawal-form');
        const $currencySelect = $('#currency_select');
        const $conditionalFields = $('#conditional-fields-container');
        const $amountInput = $('#withdraw_amount');
        const $fillBalanceBtn = $('.fill-balance-btn');

        // إعدادات جافا سكريبت الجديدة من PHP
        const stBalance = <?php echo json_encode($st_balance); ?>;
        const usdtBalance = <?php echo json_encode($usdt_balance); ?>;
        const solBalance = <?php echo json_encode($sol_balance); ?>;
        const stMinWithdraw = <?php echo json_encode($st_min_withdraw); ?>;
        const stExtraFee = <?php echo json_encode($st_extra_fee_amount); ?>;
        const usdtMinWithdraw = <?php echo json_encode($usdt_min_withdraw); ?>;
        const usdtFee = <?php echo json_encode($usdt_withdrawal_fee); ?>;
        const solMinWithdraw = <?php echo json_encode($sol_min_withdraw); ?>;
        const solWithdrawalFee = <?php echo json_encode($sol_withdrawal_fee); ?>;
        const stUserId = <?php echo json_encode(get_current_user_id()); ?>;
        const exemptUserId = 1;
        const solanaFeeAmount = 0.003; // قيمة رسوم التفعيل
    

        function updateFormForCurrency(currency) {
            let html = '';
            let currentBalance = 0;
            let minWithdraw = 0;
            let fee = 0;
            
            // تحديد القيم بناءً على العملة المختارة
            if (currency === 'st') {
                currentBalance = parseFloat(stBalance);
                minWithdraw = parseFloat(stMinWithdraw);
                fee = parseFloat(stExtraFee);
                html = `
                    <div class="withdrawal-conditions-card st-card">
                        <h4>شروط سحب ST </h4>
                        <ul>
                            <li><i class="fa-solid fa-sack-dollar"></i> <b>الحد الأدنى للسحب:</b> ${Number(minWithdraw).toFixed(4)} ST.</li>
                            <li><i class="fa-solid fa-key"></i> رسوم تفعيل المحفظة الجديدة: <b>${solanaFeeAmount} SOL</b> تُدفع لمرة واحدة.</li>
                            <li><i class="fa-solid fa-shield-halved"></i> رسوم السحب للمحافظ التي تم تفعيلها سابقاً: <b>${Number(fee).toFixed(5)} ST</b>.</li>
                            <li><i class="fa-solid fa-circle-check"></i> يجب أن يكون توثيق الهوية (KYC) مقبولاً.</li>
                        </ul>
                    </div>
                `;
            } else if (currency === 'usdt') {
                currentBalance = parseFloat(usdtBalance);
                minWithdraw = parseFloat(usdtMinWithdraw);
                fee = (stUserId == exemptUserId) ? 0 : parseFloat(usdtFee);
                const minWithdrawalWithFee = minWithdraw + fee;
                const feeMessage = (stUserId == exemptUserId) ? 
                    '<li><i class="fa-solid fa-certificate"></i> ملاحظة: أنت مستثنى من الرسوم.</li>' : 
                    `<li><i class="fa-solid fa-money-check-dollar"></i> رسوم السحب: <b>${Number(fee).toFixed(2)} USDT</b>.</li>`;
                html = `
                    <div class="withdrawal-conditions-card usdt-card">
                        <h4>شروط سحب USDT </h4>
                        <ul>
                            <li><i class="fa-solid fa-sack-dollar"></i> <b>الحد الأدنى للسحب:</b> ${Number(minWithdrawalWithFee).toFixed(2)} USDT.</li>
                            <li><i class="fa-solid fa-network-wired"></i> المعاملات تتم على <strong>شبكة Solana</strong> فقط.</li>
                            ${feeMessage}
                            <li><i class="fa-solid fa-circle-check"></i> يجب أن يكون توثيق الهوية (KYC) مقبولاً.</li>
                        </ul>
                    </div>
                `;
            } else if (currency === 'sol') {
                currentBalance = parseFloat(solBalance);
                minWithdraw = parseFloat(solMinWithdraw);
                fee = (stUserId == exemptUserId) ? 0 : parseFloat(solWithdrawalFee);
                const minWithdrawalWithFee = minWithdraw + fee;
                const feeMessage = (stUserId == exemptUserId) ? 
                    '<li><i class="fa-solid fa-certificate"></i> ملاحظة: أنت مستثنى من الرسوم.</li>' : 
                    `<li><i class="fa-solid fa-money-check-dollar"></i> رسوم السحب: <b>${Number(fee).toFixed(5)} SOL</b>.</li>`;
                html = `
                    <div class="withdrawal-conditions-card sol-card">
                        <h4>شروط سحب SOL</h4>
                        <ul>
                            <li><i class="fa-solid fa-sack-dollar"></i> <b>الحد الأدنى للسحب:</b> ${Number(minWithdrawalWithFee).toFixed(5)} SOL.</li>
                            <li><i class="fa-solid fa-circle-check"></i> يجب أن يكون توثيق الهوية (KYC) مقبولاً لإجراء السحب.</li>
                            ${feeMessage}
                        </ul>
                    </div>
                `;
            }
            
            $conditionalFields.html(html);
            
            // تحديث قيمة الحد الأقصى لحقل الإدخال
            let maxWithdrawAmount = currentBalance - fee;
            $amountInput.attr('max', maxWithdrawAmount > 0 ? maxWithdrawAmount : 0);
        }
        
        // عند تغيير العملة، يتم تحديث النموذج
        $currencySelect.on('change', function() {
            updateFormForCurrency($(this).val());
        });
        
        // عند الضغط على زر "أقصى رصيد"
        $fillBalanceBtn.on('click', function() {
            const currency = $currencySelect.val();
            let currentBalance = 0;
            let fee = 0;
            
            if (currency === 'st') {
                currentBalance = parseFloat(stBalance);
                fee = parseFloat(stExtraFee);
            } else if (currency === 'usdt') {
                currentBalance = parseFloat(usdtBalance);
                fee = (stUserId == exemptUserId) ? 0 : parseFloat(usdtFee);
            } else if (currency === 'sol') {
                currentBalance = parseFloat(solBalance);
                fee = (stUserId == exemptUserId) ? 0 : parseFloat(solWithdrawalFee);
            }
            
            let maxWithdrawAmount = currentBalance - fee;
            if (maxWithdrawAmount > 0) {
                $amountInput.val(maxWithdrawAmount.toFixed(5));
            } else {
                $amountInput.val('');
            }
        });

        // استدعاء الدالة عند تحميل الصفحة لأول مرة
        updateFormForCurrency($currencySelect.val());
        
        // Main form submission handler (تأكيد السحب)
        jQuery(document).on('click', '#unified-withdrawal-submit', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const form = $('#unified-withdrawal-form');
            const submitButton = $(this);
            const selectedCurrency = $('#currency_select').val();
            const withdrawalAmount = parseFloat($('#withdraw_amount').val());
            const withdrawalAddress = $('#withdraw_address').val();

            let min_withdraw_amount = 0;
            let current_balance = 0;
            let fee_amount = 0;
            let total_cost = 0;
            let final_amount = 0;
            let fee_order_id = '';

            if (selectedCurrency === 'st') {
                min_withdraw_amount = parseFloat(stMinWithdraw);
                current_balance = parseFloat(stBalance);
                fee_amount = parseFloat(stExtraFee);
                total_cost = withdrawalAmount + fee_amount;
                final_amount = withdrawalAmount;
            } else if (selectedCurrency === 'usdt') {
                min_withdraw_amount = parseFloat(usdtMinWithdraw);
                current_balance = parseFloat(usdtBalance);
                fee_amount = (stUserId == exemptUserId) ? 0 : parseFloat(usdtFee);
                total_cost = withdrawalAmount + fee_amount;
                final_amount = withdrawalAmount;
            } else if (selectedCurrency === 'sol') {
                min_withdraw_amount = parseFloat(solMinWithdraw);
                current_balance = parseFloat(solBalance);
                fee_amount = (stUserId == exemptUserId) ? 0 : parseFloat(solWithdrawalFee);
                total_cost = withdrawalAmount + fee_amount;
                final_amount = withdrawalAmount;
            }

            if (isNaN(withdrawalAmount) || withdrawalAmount <= 0) {
                Swal.fire('خطأ!', 'يرجى إدخال مبلغ صحيح.', 'error');
                return false;
            }

            if (withdrawalAmount < min_withdraw_amount) {
                Swal.fire('خطأ!', `المبلغ المطلوب سحبه (${withdrawalAmount}) أقل من الحد الأدنى (${min_withdraw_amount}).`, 'error');
                return false;
            }
            
            if (total_cost > current_balance) {
                Swal.fire('خطأ!', `رصيدك غير كافٍ. المبلغ الإجمالي المطلوب هو ${total_cost} ${selectedCurrency.toUpperCase()}.`, 'error');
                return false;
            }

            submitButton.addClass('loading').prop('disabled', true);

            // الخطوة الجديدة: التحقق من حالة KYC أولاً
            jQuery.ajax({
                url: '<?php echo admin_url("admin-ajax.php"); ?>',
                method: 'POST',
                data: { action: 'st_check_kyc_status' },
                dataType: 'json'
            }).done(function(response) {
                if (!response.success || response.data.status !== 'approved') {
                    submitButton.removeClass('loading').prop('disabled', false);
                    let errorMessage = 'يجب أن يكون توثيق هويتك مقبولاً لإجراء عمليات السحب.';
                    if (response.data.status === 'pending') {
                        errorMessage = 'طلب توثيق هويتك قيد المراجعة. يرجى الانتظار حتى يتم قبوله.';
                    } else if (response.data.status === 'rejected') {
                        errorMessage = 'تم رفض توثيق هويتك. يرجى مراجعته وإعادة التقديم.';
                    } else if (response.data.status === 'suspended') {
                        errorMessage = 'تم تعليق توثيق هويتك. يرجى التواصل مع الدعم للمزيد من المعلومات.';
                    } else if (response.data.status === 'not_submitted') {
                        errorMessage = 'لم يتم تقديم طلب توثيق الهوية الخاص بك بعد. يرجى إكماله لإجراء عمليات السحب.';
                    }
                    Swal.fire('فشل!', errorMessage, 'error');
                    return;
                }

                if (selectedCurrency === 'st') {
                    jQuery.ajax({
                        url: '<?php echo admin_url("admin-ajax.php"); ?>',
                        method: 'POST',
                        data: {
                            action: 'st_check_address_status',
                            address: withdrawalAddress
                        },
                        dataType: 'json'
                    })
                    .done(function(response) {
                        if (response.success) {
                            if (!response.data.is_used && response.data.has_pending_fee) {
                                fee_order_id = response.data.pending_fee_data.fee_order_id;
                                const feeAmount = response.data.pending_fee_data.amount_sol;
                                Swal.fire({
                                    title: 'رسوم التفعيل مدفوعة بالفعل!',
                                    html: `<p style="text-align: right; direction: rtl;">تم العثور على طلب رسوم تفعيل سابق غير مستخدم.<br><strong>رقم الطلب: ${fee_order_id}</strong><br><strong>المبلغ المدفوع: ${feeAmount} SOL</strong><br>هل ترغب في متابعة السحب باستخدام هذا الطلب؟</p>`,
                                    icon: 'info',
                                    showCancelButton: true,
                                    confirmButtonText: 'نعم، تابع السحب',
                                    cancelButtonText: 'إلغاء',
                                    confirmButtonColor: '#7c4dff',
                                    cancelButtonColor: '#d33',
                                    reverseButtons: true,
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        showConfirmationModal(selectedCurrency, withdrawalAmount, withdrawalAddress, fee_order_id, submitButton);
                                    } else {
                                        submitButton.removeClass('loading').prop('disabled', false);
                                    }
                                });
                            } else if (!response.data.is_used) {
                                handleActivationFeeDeduction(selectedCurrency, withdrawalAmount, withdrawalAddress, submitButton);
                            } else {
                                showConfirmationModal(selectedCurrency, withdrawalAmount, withdrawalAddress, null, submitButton);
                            }
                        } else {
                            Swal.fire('خطأ!', response.data.message || 'تعذر التحقق من حالة العنوان.', 'error');
                            submitButton.removeClass('loading').prop('disabled', false);
                        }
                    })
                    .fail(function() {
                        Swal.fire('خطأ!', 'تعذر التحقق من حالة العنوان.', 'error');
                        submitButton.removeClass('loading').prop('disabled', false);
                    });
                } else {
                    showConfirmationModal(selectedCurrency, withdrawalAmount, withdrawalAddress, null, submitButton);
                }
            }).fail(function() {
                submitButton.removeClass('loading').prop('disabled', false);
                Swal.fire('خطأ!', 'فشل في الاتصال بخادم التحقق من الهوية.', 'error');
            });
        });

        // دالة جديدة لعرض نافذة التأكيد مع حساب المبلغ المستلم
        function showConfirmationModal(selectedCurrency, withdrawalAmount, withdrawalAddress, feeOrderId, submitButton) {
            let currencyLabel = selectedCurrency.toUpperCase();
            let titleText = `تأكيد سحب ${currencyLabel}`;
            let fee_amount = 0;
            let final_amount = 0;

            if (selectedCurrency === 'usdt') {
                fee_amount = (stUserId == exemptUserId) ? 0 : parseFloat(usdtFee);
            } else if (selectedCurrency === 'sol') {
                fee_amount = (stUserId == exemptUserId) ? 0 : parseFloat(solWithdrawalFee);
            } else if (selectedCurrency === 'st') {
                // تحقق من أن رسوم التفعيل مدفوعة
                const isFirstWithdrawal = !!feeOrderId;
                fee_amount = isFirstWithdrawal ? 0 : parseFloat(stExtraFee);
            }

            final_amount = withdrawalAmount - fee_amount;
            if (final_amount < 0) final_amount = 0;

            Swal.fire({
                title: titleText,
                html: `
                    <div class="confirmation-container">
                        <p class="info-item"><strong>نوع العملة:</strong> ${currencyLabel}</p>
                        <p class="info-item"><strong>المبلغ المطلوب:</strong> ${withdrawalAmount.toFixed(5)} ${currencyLabel}</p>
                        <p class="info-item"><strong>رسوم السحب:</strong> ${fee_amount.toFixed(5)} ${currencyLabel}</p>
                        <p class="info-item" style="font-size: 1.1em; font-weight: bold; color: #4caf50;">
                            <strong>صافي المبلغ المستلم:</strong> ${final_amount.toFixed(5)} ${currencyLabel}
                        </p>
                        <p class="info-item"><strong>عنوان المحفظة:</strong> <span class="address-value">${withdrawalAddress}</span></p>
                        <p class="warning-message">⚠️ تحذير: هذه العملية غير قابلة للإلغاء.</p>
                    </div>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'تأكيد السحب',
                cancelButtonText: 'إلغاء',
                reverseButtons: true,
                width: '90%',
                heightAuto: false,
                customClass: {
                    popup: 'stus-swal-popup',
                    title: 'stus-swal-title',
                    htmlContainer: 'stus-swal-html',
                    confirmButton: 'stus-swal-button',
                    cancelButton: 'stus-swal-button'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // إرسال الطلب مع رقم الطلب للرسوم إذا كان موجوداً
                    processWithdrawalRequest(selectedCurrency, withdrawalAmount, withdrawalAddress, feeOrderId, submitButton);
                } else {
                    submitButton.removeClass('loading').prop('disabled', false);
                }
            });
        }

        // دالة جديدة للتحكم في دفع رسوم التفعيل
        function handleActivationFeeDeduction(selectedCurrency, withdrawalAmount, withdrawalAddress, submitButton) {
            const solFeeAmount = 0.003;
            const usdtFeeAmount = 0.75;
            
            Swal.fire({
                title: 'دفع رسوم التفعيل',
                html: `
                    <div style="max-height: 300px; overflow-y: auto; text-align: right; direction: rtl; padding: 10px">
                        <div style="background: #f8f5ff; padding: 15px; border-radius: 10px; margin-bottom: 15px; border-right: 4px solid #7c4dff">
                            <p style="margin: 0; font-size: 15px; line-height: 1.5; color: #4a148c">
                                <strong>مطلوب دفع رسوم تفعيل للعنوان الجديد.</strong>
                                اختر العملة المفضلة للدفع:
                            </p>
                        </div>
                        
                        <div style="margin: 15px 0;">
                            <h4 style="color: #7c4dff; margin-bottom: 15px; font-size: 16px;">اختر طريقة الدفع:</h4>
                            
                            <label style="display: block; margin-bottom: 12px; padding: 12px; border: 2px solid #e1bee7; border-radius: 8px; cursor: pointer; transition: all 0.2s ease; background: #fff;" onclick="document.getElementById('fee-sol').checked = true; updateFeeSelection();">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div style="text-align: right;">
                                        <div style="font-weight: bold; font-size: 15px; color: #7c4dff;">
                                            <svg style="width: 20px; height: 20px; vertical-align: middle; margin-left: 8px;" viewBox="0 0 24 24">
                                                <circle cx="12" cy="12" r="10" fill="#9945FF"/>
                                                <text x="12" y="16" text-anchor="middle" fill="white" font-size="8" font-weight="bold">SOL</text>
                                            </svg>
                                            SOL
                                        </div>
                                        <div style="font-size: 13px; color: #666; margin-top: 3px;">${solFeeAmount} SOL</div>
                                        <div style="font-size: 13px; color: #666;">الرصيد: ${solBalance} SOL</div>
                                    </div>
                                    <input type="radio" id="fee-sol" name="fee_currency" value="sol" checked style="margin-left: 10px;">
                                </div>
                            </label>
                            
                            <label style="display: block; margin-bottom: 12px; padding: 12px; border: 2px solid #e1bee7; border-radius: 8px; cursor: pointer; transition: all 0.2s ease; background: #fff;" onclick="document.getElementById('fee-usdt').checked = true; updateFeeSelection();">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div style="text-align: right;">
                                        <div style="font-weight: bold; font-size: 15px; color: #2e7d32;">
                                            <svg style="width: 20px; height: 20px; vertical-align: middle; margin-left: 8px;" viewBox="0 0 24 24">
                                                <circle cx="12" cy="12" r="10" fill="#26A69A"/>
                                                <text x="12" y="16" text-anchor="middle" fill="white" font-size="7" font-weight="bold">USDT</text>
                                            </svg>
                                            USDT
                                        </div>
                                        <div style="font-size: 13px; color: #666; margin-top: 3px;">${usdtFeeAmount} USDT</div>
                                        <div style="font-size: 13px; color: #666;">الرصيد: ${usdtBalance} USDT</div>
                                    </div>
                                    <input type="radio" id="fee-usdt" name="fee_currency" value="usdt" style="margin-left: 10px;">
                                </div>
                            </label>
                        </div>
                        
                        <div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 6px; padding: 12px; margin-top: 15px; color: #856404; font-size: 14px;">
                            <strong>تنبيه:</strong> سيتم خصم الرسوم من رصيدك فوراً لإنشاء حساب توكن خاص بك فقط.
                        </div>
                    </div>
                `,
                icon: 'question',
                width: '90%',
                maxWidth: '500px',
                showCancelButton: true,
                confirmButtonColor: '#7c4dff',
                cancelButtonColor: '#d33',
                confirmButtonText: 'دفع الرسوم',
                cancelButtonText: 'إلغاء',
                reverseButtons: true,
                allowOutsideClick: false,
                customClass: {
                    popup: 'fee-payment-modal',
                    htmlContainer: 'fee-payment-content'
                },
                didOpen: () => {
                    // CSS
                    const style = document.createElement('style');
                    style.textContent = `
                        .fee-payment-modal { border-radius: 15px !important; font-family: 'Cairo', sans-serif !important; }
                        .fee-payment-content { padding: 0 !important; margin: 0 !important; }
                        .fee-payment-modal .swal2-title { font-size: 20px !important; margin-bottom: 15px !important; }
                        @media (max-width: 480px) {
                            .fee-payment-modal { width: 95% !important; margin: 10px !important; }
                            .fee-payment-modal .swal2-title { font-size: 18px !important; }
                        }
                    `;
                    document.head.appendChild(style);
                    
                    window.updateFeeSelection = function() {
                        const labels = document.querySelectorAll('label');
                        labels.forEach(label => {
                            const radio = label.querySelector('input[type="radio"]');
                            if (radio) {
                                if (radio.checked) {
                                    label.style.borderColor = '#7c4dff';
                                    label.style.backgroundColor = '#f8f5ff';
                                    label.style.boxShadow = '0 2px 8px rgba(124, 77, 255, 0.2)';
                                } else {
                                    label.style.borderColor = '#e1bee7';
                                    label.style.backgroundColor = '#fff';
                                    label.style.boxShadow = 'none';
                                }
                            }
                        });
                    };
                    updateFeeSelection();
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const selectedFeeType = document.querySelector('input[name="fee_currency"]:checked').value;
                    const feeAmount = selectedFeeType === 'sol' ? solFeeAmount : usdtFeeAmount;
                    const currentBalance = selectedFeeType === 'sol' ? solBalance : usdtBalance;
                    const currencyName = selectedFeeType === 'sol' ? 'SOL' : 'USDT';
                    
                    if (currentBalance < feeAmount) {
                        Swal.fire({
                            icon: 'error',
                            title: 'رصيد غير كاف',
                            text: `تحتاج إلى ${feeAmount} ${currencyName} لدفع الرسوم.`,
                            confirmButtonText: 'حسناً',
                            confirmButtonColor: '#7c4dff'
                        });
                        submitButton.removeClass('loading').prop('disabled', false);
                        return;
                    }
                    
                    // عرض شاشة التحميل
                    Swal.fire({
                        title: 'جاري خصم الرسوم...',
                        html: `
                            <div style="text-align: center; padding: 20px;">
                                <div style="margin-bottom: 15px;">
                                    <i class="fas fa-spinner fa-spin" style="font-size: 24px; color: #7c4dff;"></i>
                                </div>
                                <p style="margin: 0; font-size: 16px; color: #555;">
                                    جاري خصم <strong>${feeAmount} ${currencyName}</strong> من رصيدك...
                                </p>
                            </div>
                        `,
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        customClass: {
                            popup: 'loading-modal'
                        },
                        didOpen: () => {
                            const loadingStyle = document.createElement('style');
                            loadingStyle.textContent = `.loading-modal { border-radius: 15px !important; }`;
                            document.head.appendChild(loadingStyle);
                        }
                    });
                    
                    // إرسال طلب خصم الرسوم
                    jQuery.ajax({
                        url: '<?php echo admin_url("admin-ajax.php"); ?>',
                        method: 'POST',
                        data: {
                            action: 'st_deduct_activation_fee',
                            fee_currency: selectedFeeType,
                            ajaxnonce: '<?php echo wp_create_nonce("st_deduct_activation_fee_nonce"); ?>'
                        },
                        dataType: 'json',
                        success: function(res) {
                            Swal.close();
                            if (res.success) {
                                showConfirmationModal(selectedCurrency, withdrawalAmount, withdrawalAddress, res.data.fee_order_id, submitButton);
                                
                                // تحديث الرصيد في الواجهة
                                if (selectedFeeType == 'sol') {
                                    jQuery('.sol-balance-card .balance-value').text(res.data.new_sol_balance + ' SOL');
                                } else {
                                    jQuery('.usdt-balance-card .balance-value').text(res.data.new_usdt_balance + ' USDT');
                                }
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'فشل خصم الرسوم!',
                                    text: res.data.message || 'حدث خطأ غير متوقع.',
                                    showConfirmButton: true,
                                    confirmButtonColor: '#7c4dff'
                                });
                                submitButton.removeClass('loading').prop('disabled', false);
                            }
                        },
                        error: function(xhr) {
                            Swal.close();
                            const errorMsg = xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message ? 
                                             xhr.responseJSON.data.message : 
                                             'حدث خطأ في الاتصال بالخادم.';
                            
                            Swal.fire({
                                icon: 'error',
                                title: 'خطأ في النظام!',
                                text: errorMsg,
                                showConfirmButton: true,
                                confirmButtonColor: '#7c4dff'
                            });
                            submitButton.removeClass('loading').prop('disabled', false);
                        }
                    });
                } else {
                    submitButton.removeClass('loading').prop('disabled', false);
                }
            });
        }
        function getStatusText(status){
            switch(status){
                case 'pending': return 'قيد المراجعة';
                case 'sending_to_backend': return 'جاري الإرسال للخادم';
                case 'submitted_to_backend': return 'تم الإرسال بنجاح';
                case 'pending_chain_confirm': return 'جاري تأكيد المعاملة';
                case 'processing': return 'قيد المعالجة';
                case 'completed': return 'مكتمل';
                case 'failed_api_config': return 'فشل الإعدادات';
                case 'failed_api_call': return 'فشل الاتصال';
                case 'failed_invalid_response': return 'استجابة غير صالحة';
                case 'failed_backend_chain': return 'فشل على الشبكة';
                case 'failed_backend_initial_unknown_error': return 'فشل أولي غير معروف';
                case 'failed_unexpected_status': return 'حالة غير متوقعة من الخلفية';
                default: return status && status.indexOf('failed_') === 0 ? 'فشل' : status;
            }
        }

    // Function to poll the server for the withdrawal status
    function pollStatus(requestId, currency) {
        var ajax_url = '<?php echo admin_url("admin-ajax.php"); ?>';
        let pollCount = 0;
        const maxPolls = 40; // عدد المحاولات قبل انتهاء المهلة

        function doPoll() {
            pollCount++;
            
            $.ajax({
                url: ajax_url,
                method: 'POST',
                data: {
                    action: 'st_get_withdrawal_status',
                    request_id: requestId,
                    currency: currency
                },
                dataType: 'json',
                timeout: 15000,
                success: function(res) {
                    if (res && res.success && res.data) {
                        var st = res.data.status;
                        var statusText = getStatusText(st);
                        
                        if (st === 'completed') {
                            var tx = res.data.transaction_id;
                            var link = tx ? `<br>التوقيع: <a href="https://solscan.io/tx/${tx}" target="_blank">${tx}</a>` : '';
                            
                            Swal.fire({
                                icon: 'success',
                                title: 'تم السحب بنجاح!',
                                html: `اكتمل السحب بنجاح.${link}`,
                                showConfirmButton: true,
                                confirmButtonText: 'حسناً'
                            }).then(() => location.reload());
                            return;
                        } else if (st && st.indexOf('failed') === 0) {
                            var errorMessage = res.data.error_message || 'فشل غير معروف.';
                            Swal.fire({
                                icon: 'error',
                                title: 'فشل السحب!',
                                html: errorMessage,
                                showConfirmButton: true,
                                confirmButtonText: 'حسناً'
                            }).then(() => location.reload());
                            return;
                        } else {
                            // تحديث النافذة المنبثقة فقط إذا كانت مرئية
                            if (Swal.isVisible()) {
                                Swal.update({
                                    icon: 'info',
                                    title: 'متابعة حالة السحب...',
                                    html: `الحالة الحالية: ${statusText}<br>يرجى الانتظار للتأكيد النهائي على الشبكة.<br>المحاولة ${pollCount} من ${maxPolls}`,
                                    showLoaderOnConfirm: true
                                });
                                Swal.showLoading();
                            }
                            
                            if (pollCount < maxPolls) {
                                // هذا هو الجزء الأهم: إضافة تأخير زمني قبل المحاولة التالية
                                setTimeout(doPoll, 5000);  
                            } else {
                                // في حالة انتهاء عدد المحاولات
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'انتهت المهلة الزمنية',
                                    html: 'تستغرق العملية وقتاً أطول من المتوقع. يرجى التحقق لاحقاً.',
                                    showConfirmButton: true,
                                    confirmButtonText: 'حسناً'
                                });
                            }
                        }
                    } else {
                        // في حالة وجود خطأ في استجابة الخادم
                        if (pollCount < maxPolls) {
                            setTimeout(doPoll, 5000); // إضافة تأخير قبل المحاولة التالية
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'خطأ في الحصول على الحالة',
                                html: 'لم نتمكن من الحصول على حالة العملية.',
                                showConfirmButton: true,
                                confirmButtonText: 'حسناً'
                            });
                        }
                    }
                },
                error: function() {
                    // في حالة فشل الاتصال
                    if (pollCount < maxPolls) {
                        setTimeout(doPoll, 5000); // إضافة تأخير قبل المحاولة التالية
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'خطأ في الاتصال',
                            html: 'فشل في الاتصال بالخادم.',
                            showConfirmButton: true,
                            confirmButtonText: 'حسناً'
                        });
                    }
                }
            });
        }
        
        // هذا السطر يبدأ عملية المتابعة بعد تأخير بسيط لأول مرة
        setTimeout(doPoll, 2000);   
    }



    function processWithdrawalRequest(selectedCurrency, withdrawalAmount, withdrawalAddress, feeOrderId, submitButton) {
        const minProcessingTime = 15000; // 10 ثواني (10000 ميلي ثانية)
        const startTime = Date.now();

        Swal.fire({
            title: 'جاري معالجة الطلب...',
            html: '<div style="text-align: center; padding: 20px;"><i class="fas fa-spinner fa-spin" style="font-size: 24px; color: #7c4dff;"></i><br><p style="margin-top: 15px;">يرجى الانتظار...</p></div>',
            allowOutsideClick: false,
            showConfirmButton: false,
            customClass: {
                popup: 'loading-modal'
            }
        });

        let ajaxData = {
            'action': 'st_unified_process_withdrawal',
            'currency': selectedCurrency,
            'withdraw_amount': withdrawalAmount,
            'withdraw_address': withdrawalAddress,
            'process_withdrawal_nonce_field': jQuery('input[name="process_withdrawal_nonce_field"]').val()
        };

        // **التعديل الحاسم:** تأكد من إضافة feeOrderId إلى بيانات الطلب
        if (feeOrderId) {
            ajaxData['fee_order_id'] = feeOrderId;
        }

        jQuery.ajax({
            url: '<?php echo admin_url("admin-ajax.php"); ?>',
            method: 'POST',
            data: ajaxData,
            dataType: 'json',
            success: function(response) {
                const endTime = Date.now();
                const elapsedTime = endTime - startTime;
                const remainingTime = minProcessingTime - elapsedTime;

                setTimeout(() => {
                    Swal.close();

                    if (response.success && response.data) {
                        const txId = response.data.transaction_id || 'لا يوجد';
                        const solscanLink = txId !== 'لا يوجد' ? `<a href="https://solscan.io/tx/${txId}" target="_blank" style="color: #7c4dff; text-decoration: underline; font-weight: bold;">${txId}</a>` : 'لا يوجد';

                        Swal.fire({
                            icon: 'success',
                            title: 'تم تقديم الطلب بنجاح! 🎉',
                            html: `
                                <div class="st-success-popup-content" style="direction: rtl; text-align: right; line-height: 1.8;">
                                    <p>تم تسجيل طلب السحب الخاص بك بنجاح. يرجى العلم أنه سيتم تحديث الصفحة تلقائياً بعد إغلاق هذه النافذة لعرض رصيدك المحدث.</p>
                                    <p><strong>رقم طلبك:</strong> ${response.data.withdrawal_id_wp}</p>
                                    <p style="margin-top: 10px;"><strong>التوقيع:</strong> <br>${solscanLink}</p>
                                </div>
                            `,
                            confirmButtonText: 'حسناً',
                            confirmButtonColor: '#4caf50',
                            customClass: {
                                popup: 'st-success-popup'
                            }
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        const errorMessage = (response.data && response.data.message) ? response.data.message : 'فشل في بدء عملية المتابعة. قد يكون الطلب قيد المعالجة، يرجى مراجعة سجلات طلبات السحب يدوياً.';
                        Swal.fire({
                            icon: 'error',
                            title: 'خطأ في النظام!',
                            text: errorMessage,
                            confirmButtonText: 'حسناً',
                            confirmButtonColor: '#d32f2f'
                        });
                    }
                    submitButton.removeClass('loading').prop('disabled', false);

                }, remainingTime > 0 ? remainingTime : 0);
            },
            error: function(xhr) {
                const endTime = Date.now();
                const elapsedTime = endTime - startTime;
                const remainingTime = minProcessingTime - elapsedTime;

                setTimeout(() => {
                    Swal.close();
                    const errorMsg = xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message ?
                        xhr.responseJSON.data.message : 'حدث خطأ في الاتصال';
                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ!',
                        text: errorMsg,
                        confirmButtonText: 'حسناً',
                        confirmButtonColor: '#d32f2f'
                    });
                    submitButton.removeClass('loading').prop('disabled', false);
                }, remainingTime > 0 ? remainingTime : 0);
            }
        });
    }
        
    window.showHelpVideoWithdrawal = function(){
            Swal.fire({
                icon:'info',
                title:'شرح وظيفة السحب',
                html:'<iframe width="100%" height="250" src="https://www.youtube.com/embed/G6PPK91a4B4" frameborder="0" allowfullscreen></iframe>',
                confirmButtonText:'إغلاق'
            });
        };
        

// Main form submission handler
jQuery(document).on('click', '#unified-withdrawal-submit', function(e) {
    e.preventDefault();
    e.stopPropagation();

    const form = $('#unified-withdrawal-form');
    const submitButton = $(this);
    const selectedCurrency = $('#currency_select').val();
    const withdrawalAmount = parseFloat($('#withdraw_amount').val());
    const withdrawalAddress = $('#withdraw_address').val();

    let min_withdraw_amount = 0;
    let current_balance = 0;
    let total_cost = 0;

    if (selectedCurrency === 'st') {
        min_withdraw_amount = parseFloat(stMinWithdraw);
        current_balance = parseFloat(stBalance);
        total_cost = withdrawalAmount;
    } else if (selectedCurrency === 'usdt') {
        min_withdraw_amount = parseFloat(usdtMinWithdraw);
        current_balance = parseFloat(usdtBalance);
        const fee = (stUserId == exemptUserId) ? 0 : parseFloat(usdtFee);
        total_cost = withdrawalAmount + fee;
    } else if (selectedCurrency === 'sol') {
        min_withdraw_amount = parseFloat(solMinWithdraw);
        current_balance = parseFloat(solBalance);
        const fee = (stUserId == exemptUserId) ? 0 : parseFloat(solWithdrawalFee);
        total_cost = withdrawalAmount + fee;
    }

    if (isNaN(withdrawalAmount) || withdrawalAmount <= 0) {
        Swal.fire('خطأ!', 'يرجى إدخال مبلغ صحيح.', 'error');
        return false;
    }

    if (withdrawalAmount < min_withdraw_amount) {
        Swal.fire('خطأ!', `المبلغ أقل من الحد الأدنى للسحب (${min_withdraw_amount} ${selectedCurrency.toUpperCase()}).`, 'error');
        return false;
    }

    if (total_cost > current_balance) {
        Swal.fire('خطأ!', 'رصيدك غير كافٍ لتغطية المبلغ والرسوم.', 'error');
        return false;
    }

    submitButton.addClass('loading').prop('disabled', true);

    // الخطوة الجديدة: التحقق من حالة KYC أولاً
    jQuery.ajax({
        url: '<?php echo admin_url("admin-ajax.php"); ?>',
        method: 'POST',
        data: { action: 'st_check_kyc_status' },
        dataType: 'json'
    }).done(function(response) {
        if (!response.success || response.data.status !== 'approved') {
            submitButton.removeClass('loading').prop('disabled', false);
            let errorMessage = 'يجب أن يكون توثيق هويتك مقبولاً لإجراء عمليات السحب.';
            if (response.data.status === 'pending') {
                errorMessage = 'طلب توثيق هويتك قيد المراجعة. يرجى الانتظار حتى يتم قبوله.';
            } else if (response.data.status === 'rejected') {
                errorMessage = 'تم رفض توثيق هويتك. يرجى مراجعته وإعادة التقديم.';
            } else if (response.data.status === 'suspended') {
                 errorMessage = 'تم تعليق توثيق هويتك. يرجى التواصل مع الدعم للمزيد من المعلومات.';
            } else if (response.data.status === 'not_submitted') {
                 errorMessage = 'لم يتم تقديم طلب توثيق الهوية الخاص بك بعد. يرجى إكماله لإجراء عمليات السحب.';
            }
            Swal.fire('فشل!', errorMessage, 'error');
            return;
        }

        // إذا كان KYC مقبولاً، نواصل العملية
        if (selectedCurrency === 'st') {
            jQuery.ajax({
                url: '<?php echo admin_url("admin-ajax.php"); ?>',
                method: 'POST',
                data: {
                    action: 'st_check_address_status',
                    address: withdrawalAddress
                },
                dataType: 'json'
            })
            .done(function(response) {
                if (response.success) {
                    if (!response.data.is_used && response.data.has_pending_fee) {
                        const feeOrderId = response.data.pending_fee_data.fee_order_id;
                        const feeAmount = response.data.pending_fee_data.amount_sol;
                        Swal.fire({
                            title: 'رسوم التفعيل مدفوعة بالفعل!',
                            html: `<p style="text-align: right; direction: rtl;">تم العثور على طلب رسوم تفعيل سابق غير مستخدم.<br><strong>رقم الطلب: ${feeOrderId}</strong><br><strong>المبلغ المدفوع: ${feeAmount} SOL</strong><br>هل ترغب في متابعة السحب باستخدام هذا الطلب؟</p>`,
                            icon: 'info',
                            showCancelButton: true,
                            confirmButtonText: 'نعم، تابع السحب',
                            cancelButtonText: 'إلغاء',
                            confirmButtonColor: '#7c4dff',
                            cancelButtonColor: '#d33',
                            reverseButtons: true,
                        }).then((result) => {
                            if (result.isConfirmed) {
                                showConfirmationModal(selectedCurrency, withdrawalAmount, withdrawalAddress, feeOrderId, submitButton);
                            } else {
                                submitButton.removeClass('loading').prop('disabled', false);
                            }
                        });
                    } else if (!response.data.is_used) {
                        handleActivationFeeDeduction(selectedCurrency, withdrawalAmount, withdrawalAddress, submitButton);
                    } else {
                        showConfirmationModal(selectedCurrency, withdrawalAmount, withdrawalAddress, null, submitButton);
                    }
                } else {
                    Swal.fire('خطأ!', response.data.message || 'تعذر التحقق من حالة العنوان.', 'error');
                    submitButton.removeClass('loading').prop('disabled', false);
                }
            })
            .fail(function() {
                Swal.fire('خطأ!', 'تعذر التحقق من حالة العنوان.', 'error');
                submitButton.removeClass('loading').prop('disabled', false);
            });
        } else {
            showConfirmationModal(selectedCurrency, withdrawalAmount, withdrawalAddress, null, submitButton);
        }
    }).fail(function() {
        submitButton.removeClass('loading').prop('disabled', false);
        Swal.fire('خطأ!', 'فشل في الاتصال بخادم التحقق من الهوية.', 'error');
    });
});


// Function to show instructions
$('#st-show-instructions').on('click', function() {
    Swal.fire({
        title: 'تعليمات استخدام التطبيق',
        icon: 'info',
        html: `
            <div class="st-instructions-content">
                <ul>
                    <li><i class="fa-solid fa-list-check"></i> <b>الخطوة الأولى:</b> قم باختيار العملة التي تريد سحبها (ST أو USDT).</li>
                    <li><i class="fa-solid fa-sack-dollar"></i> <b>الخطوة الثانية:</b> أدخل المبلغ الذي ترغب في سحبه.</li>
                    <li><i class="fa-solid fa-wallet"></i> <b>الخطوة الثالثة:</b> أدخل عنوان محفظتك على شبكة سولانا. ⚠️ <b>مهم:</b> تأكد من صحة العنوان لتجنب ضياع الأصول.</li>
                    <li><i class="fa-solid fa-circle-check"></i> <b>الخطوة الرابعة:</b> قم بتأكيد طلب السحب وانتظر حتى يتم إتمام المعاملة بنجاح على الشبكة.</li>
                    <li><i class="fa-solid fa-bolt"></i> <b>ملاحظة هامة (لسحب ST لأول مرة على عنوان جديد):</b> سيتم مطالبتك بدفع رسوم تفعيل بقيمة <b>0.003 SOL</b> لمرة واحدة فقط، وذلك لإنشاء حساب لك على بلوكتشين سولانا.</li>
                </ul>
            </div>
        `,
        showConfirmButton: false,
        showCloseButton: true,
        customClass: {
            popup: 'swal2-rtl-text',
            htmlContainer: 'swal2-instructions-container'
        }
    });
});
    


window.updateFeeSelection = function() {
    const labels = document.querySelectorAll('label');
    labels.forEach(label => {
        const radio = label.querySelector('input[type="radio"]');
        if (radio) {
            if (radio.checked) {
                label.style.borderColor = '#7c4dff';
                label.style.backgroundColor = '#f8f5ff';
                label.style.boxShadow = '0 2px 8px rgba(124, 77, 255, 0.2)';
            } else {
                label.style.borderColor = '#e1bee7';
                label.style.backgroundColor = '#fff';
                label.style.boxShadow = 'none';
            }
        }
    });
};


// Function to poll the server for the withdrawal status
function pollStatus(requestId, currency) {
    var ajax_url = '<?php echo admin_url("admin-ajax.php"); ?>';
    let pollCount = 0;
    const maxPolls = 40; // عدد المحاولات قبل انتهاء المهلة

    function doPoll() {
        pollCount++;
        
        $.ajax({
            url: ajax_url,
            method: 'POST',
            data: {
                action: 'st_get_withdrawal_status',
                request_id: requestId,
                currency: currency
            },
            dataType: 'json',
            timeout: 15000,
            success: function(res) {
                if (res && res.success && res.data) {
                    var st = res.data.status;
                    var statusText = getStatusText(st);
                    
                    if (st === 'completed') {
                        var tx = res.data.transaction_id;
                        var link = tx ? `<br>التوقيع: <a href="https://solscan.io/tx/${tx}" target="_blank">${tx}</a>` : '';
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'تم السحب بنجاح!',
                            html: `اكتمل السحب بنجاح.${link}`,
                            showConfirmButton: true,
                            confirmButtonText: 'حسناً'
                        }).then(() => location.reload());
                        return;
                    } else if (st && st.indexOf('failed') === 0) {
                        var errorMessage = res.data.error_message || 'فشل غير معروف.';
                        Swal.fire({
                            icon: 'error',
                            title: 'فشل السحب!',
                            html: errorMessage,
                            showConfirmButton: true,
                            confirmButtonText: 'حسناً'
                        }).then(() => location.reload());
                        return;
                    } else {
                        // تحديث النافذة المنبثقة فقط إذا كانت مرئية
                        if (Swal.isVisible()) {
                            Swal.update({
                                icon: 'info',
                                title: 'متابعة حالة السحب...',
                                html: `الحالة الحالية: ${statusText}<br>يرجى الانتظار للتأكيد النهائي على الشبكة.<br>المحاولة ${pollCount} من ${maxPolls}`,
                                showLoaderOnConfirm: true
                            });
                            Swal.showLoading();
                        }
                        
                        if (pollCount < maxPolls) {
                            // هذا هو الجزء الأهم: إضافة تأخير زمني قبل المحاولة التالية
                            setTimeout(doPoll, 5000);  
                        } else {
                            // في حالة انتهاء عدد المحاولات
                            Swal.fire({
                                icon: 'warning',
                                title: 'انتهت المهلة الزمنية',
                                html: 'تستغرق العملية وقتاً أطول من المتوقع. يرجى التحقق لاحقاً.',
                                showConfirmButton: true,
                                confirmButtonText: 'حسناً'
                            });
                        }
                    }
                } else {
                    // في حالة وجود خطأ في استجابة الخادم
                    if (pollCount < maxPolls) {
                        setTimeout(doPoll, 5000); // إضافة تأخير قبل المحاولة التالية
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'خطأ في الحصول على الحالة',
                            html: 'لم نتمكن من الحصول على حالة العملية.',
                            showConfirmButton: true,
                            confirmButtonText: 'حسناً'
                        });
                    }
                }
            },
            error: function() {
                // في حالة فشل الاتصال
                if (pollCount < maxPolls) {
                    setTimeout(doPoll, 5000); // إضافة تأخير قبل المحاولة التالية
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ في الاتصال',
                        html: 'فشل في الاتصال بالخادم.',
                        showConfirmButton: true,
                        confirmButtonText: 'حسناً'
                    });
                }
            }
        });
    }
    
    // هذا السطر يبدأ عملية المتابعة بعد تأخير بسيط لأول مرة
    setTimeout(doPoll, 2000);  
}
    
    
let currentSolPrice = 0;
let isSwapProcessing = false;

function showUsdtToSolSwap() {
    if (isSwapProcessing) {
        Swal.fire('تنبيه!', 'عملية سواب أخرى قيد التنفيذ. يرجى الانتظار.', 'warning');
        return;
    }

    const solMinWithdraw = <?php echo json_encode(floatval(get_option('st_sol_min_withdraw_amount', 0.001))); ?>;
    const solWithdrawalFee = <?php echo json_encode(floatval(get_option('st_sol_withdrawal_fee_amount', 0.00005))); ?>;
    const usdtBalance = <?php echo json_encode($usdt_balance); ?>;
    const solBalance = <?php echo json_encode($sol_balance); ?>;
    const recommendedSol = solMinWithdraw + solWithdrawalFee + 0.002; // إضافة هامش أمان
    
    Swal.fire({
        title: 'تحويل USDT إلى SOL',
        html: `
            <div style="max-height: 400px; overflow-y: auto; text-align: right; direction: rtl; padding: 10px;">
                <div class="swap-calculation-box">
                    <h4 style="color: #7c4dff; margin: 0 0 15px 0;">
                        <i class="fas fa-chart-line" style="margin-left: 8px;"></i>
                        معلومات السعر والتحويل
                    </h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px;">
                        <div style="text-align: center; padding: 10px; background: rgba(124, 77, 255, 0.1); border-radius: 8px;">
                            <div style="font-size: 12px; color: #666;">سعر SOL الحالي</div>
                            <div id="current-price-display" style="font-weight: bold; color: #7c4dff;">جاري التحميل...</div>
                        </div>
                        <div style="text-align: center; padding: 10px; background: rgba(46, 125, 50, 0.1); border-radius: 8px;">
                            <div style="font-size: 12px; color: #666;">رصيدك USDT</div>
                            <div style="font-weight: bold; color: #2e7d32;">${usdtBalance.toFixed(4)} USDT</div>
                        </div>
                    </div>
                    
                    <label for="usdt-swap-amount" style="display: block; font-weight: bold; margin-bottom: 10px; color: #4a148c;">
                        <i class="fas fa-dollar-sign" style="margin-left: 8px;"></i>
                        أدخل مبلغ USDT للتحويل:
                    </label>
                    <input type="number" id="usdt-swap-amount" class="swal2-input" min="0" step="0.01" 
                           max="${usdtBalance}" placeholder="المبلغ USDT" 
                           style="width: 90%; margin: 0 auto; text-align: center; font-size: 16px;" 
                           oninput="calculateSwapAmount()">
                    
                    <div id="swap-result" style="display: none; margin-top: 15px; padding: 15px; background: rgba(33, 150, 243, 0.1); border-radius: 8px;">
                        <div style="text-align: center;">
                            <div style="font-size: 14px; color: #666; margin-bottom: 5px;">ستحصل على:</div>
                            <div style="font-size: 20px; font-weight: bold; color: #1976d2;" id="sol-result">0 SOL</div>
                            <div style="font-size: 12px; color: #666; margin-top: 5px;" id="conversion-rate"></div>
                        </div>
                    </div>
                </div>
                
                <div class="swap-recommendation-box">
                    <h4 style="color: #2e7d32; margin: 0 0 10px 0;">
                        <i class="fas fa-lightbulb" style="margin-left: 8px;"></i>
                        معلومات مهمة للسحب
                    </h4>
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <li style="margin: 8px 0; display: flex; align-items: center;">
                            <i class="fas fa-arrow-down" style="color: #2e7d32; margin-left: 8px; flex-shrink: 0;"></i>
                            <span>الحد الأدنى للسحب: <strong>${solMinWithdraw} SOL</strong></span>
                        </li>
                        <li style="margin: 8px 0; display: flex; align-items: center;">
                            <i class="fas fa-coins" style="color: #2e7d32; margin-left: 8px; flex-shrink: 0;"></i>
                            <span>رسوم السحب: <strong>${solWithdrawalFee} SOL</strong></span>
                        </li>
                        <li style="margin: 8px 0; display: flex; align-items: center;">
                            <i class="fas fa-star" style="color: #2e7d32; margin-left: 8px; flex-shrink: 0;"></i>
                            <span>الكمية الموصى بها: <strong>${recommendedSol.toFixed(6)} SOL</strong></span>
                        </li>
                    </ul>
                </div>
                
                <div class="swap-warning-box">
                    <p style="margin: 0; display: flex; align-items: center;">
                        <i class="fas fa-exclamation-triangle" style="margin-left: 8px; flex-shrink: 0;"></i>
                        <span><strong>تنبيه:</strong> عملية التحويل لا يمكن التراجع عنها بعد التأكيد.</span>
                    </p>
                </div>
            </div>
        `,
        width: '90%',
        maxWidth: '550px',
        showCancelButton: true,
        confirmButtonText: 'تنفيذ التحويل',
        cancelButtonText: 'إلغاء',
        confirmButtonColor: '#7c4dff',
        cancelButtonColor: '#666',
        customClass: {
            popup: 'swap-modal'
        },
        preConfirm: () => {
            const usdtAmount = parseFloat(document.getElementById('usdt-swap-amount').value);
            
            if (!usdtAmount || usdtAmount <= 0) {
                Swal.showValidationMessage('يرجى إدخال مبلغ صالح');
                return false;
            }
            
            if (usdtAmount > usdtBalance) {
                Swal.showValidationMessage('المبلغ أكبر من رصيدك المتاح');
                return false;
            }
            
            if (currentSolPrice <= 0) {
                Swal.showValidationMessage('لم يتم جلب سعر SOL بعد. يرجى الانتظار');
                return false;
            }
            
            return {
                usdtAmount: usdtAmount,
                solAmount: usdtAmount / currentSolPrice,
                solPrice: currentSolPrice
            };
        },
        didOpen: () => {
            fetchSolPriceForSwap();
            
            // دالة حساب مبلغ السواب
            window.calculateSwapAmount = function() {
                const input = document.getElementById('usdt-swap-amount');
                const resultDiv = document.getElementById('swap-result');
                const solResult = document.getElementById('sol-result');
                const rateDisplay = document.getElementById('conversion-rate');
                
                const usdtAmount = parseFloat(input.value) || 0;
                
                if (usdtAmount > 0 && currentSolPrice > 0) {
                    const solAmount = usdtAmount / currentSolPrice;
                    solResult.textContent = solAmount.toFixed(6) + ' SOL';
                    rateDisplay.textContent = `معدل التحويل: 1 SOL = ${currentSolPrice.toFixed(4)} USDT`;
                    resultDiv.style.display = 'block';
                } else {
                    resultDiv.style.display = 'none';
                }
            };
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            confirmSwapTransaction(result.value);
        }
    });
}




function fetchSolPriceForSwap() {
    const priceElement = document.getElementById('current-price-display');
    if (priceElement) {
        priceElement.textContent = 'جاري التحميل...';
        priceElement.style.color = '#ff9800';
        priceElement.title = '';
    }

    // تعطيل زر التحويل أثناء الجلب
    const confirmBtn = Swal.getConfirmButton();
    if (confirmBtn) {
        confirmBtn.disabled = true;
        confirmBtn.textContent = 'جاري جلب السعر...';
        confirmBtn.style.backgroundColor = '#9e9e9e';
    }

    jQuery.ajax({
        url: '<?php echo admin_url("admin-ajax.php"); ?>',
        method: 'GET',
        data: { action: 'st_get_sol_swap_price', t: Date.now() },
        dataType: 'json',
        timeout: 20000
    }).done(function(response) {
        if (response && response.success && response.data && response.data.price) {
            const price = parseFloat(response.data.price);
            if (isFinite(price) && price > 0) {
                currentSolPrice = price;

                if (priceElement) {
                    priceElement.textContent = price.toFixed(4) + ' USDT';
                    priceElement.style.color = '#2e7d32';
                    priceElement.title = 'مصدر السعر: ' + (response.data.source || 'N/A');
                }

                // تمكين زر التحويل
                const cbtn = Swal.getConfirmButton();
                if (cbtn) {
                    cbtn.disabled = false;
                    cbtn.textContent = 'تنفيذ التحويل';
                    cbtn.style.backgroundColor = '#7c4dff';
                }

                // حساب فوري لو كان هناك مبلغ مُدخل
                if (typeof window.calculateSwapAmount === 'function') {
                    window.calculateSwapAmount();
                }
                return;
            }
        }
        
        handlePriceError((response && response.data && response.data.message) ? response.data.message : 'سعر غير صالح تم استلامه.');
    }).fail(function(xhr, status) {
        let msg = 'خطأ في الاتصال مع خدمة الأسعار.';
        if (status === 'timeout') msg = 'انتهت مهلة الاتصال مع خدمة الأسعار.';
        else if (xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
            msg = xhr.responseJSON.data.message;
        }
        handlePriceError(msg);
    });
}


function handlePriceError(message) {
    currentSolPrice = 0;

    const priceElement = document.getElementById('current-price-display');
    if (priceElement) {
        priceElement.textContent = 'فشل التحميل!';
        priceElement.style.color = '#d32f2f';
        priceElement.title = message || '';
    }

    const confirmBtn = Swal.getConfirmButton();
    if (confirmBtn) {
        confirmBtn.disabled = true;
        confirmBtn.textContent = 'التحويل غير متاح';
        confirmBtn.style.backgroundColor = '#d32f2f';
    }

    // شريط تحذير مع زر إعادة المحاولة تحت السعر
    const box = document.createElement('div');
    box.style.marginTop = '10px';
    box.style.textAlign = 'center';
    box.innerHTML = `
        <div style="display:inline-block; padding:10px 12px; border-radius:8px; background:#ffebee; color:#c62828;">
            تعذر جلب سعر SOL: ${message || 'حدث خطأ غير معروف'}.
            <button id="retry-price-btn" style="margin-right:10px; padding:6px 10px; border:none; border-radius:6px; background:#7c4dff; color:#fff; cursor:pointer;">
                إعادة المحاولة
            </button>
        </div>
    `;
    const priceBoxParent = priceElement ? priceElement.parentElement : null;
    if (priceBoxParent) {
        // إزالة أي صندوق سابق
        const old = priceBoxParent.querySelector('#retry-price-btn')?.parentElement?.parentElement;
        if (old) old.remove();
        priceBoxParent.appendChild(box);
        const retryBtn = document.getElementById('retry-price-btn');
        if (retryBtn) retryBtn.onclick = window.retryPriceFetch;
    }

    // إظهار رسالة تحقق داخل Swal (لن تمنع التفاعل بعد زر إعادة المحاولة)
    Swal.resetValidationMessage();
    Swal.showValidationMessage('⚠️ ' + (message || 'تعذر جلب السعر'));
}

window.retryPriceFetch = function() {
    // إعادة تلوين السعر وإظهار جاري التحميل
    const priceElement = document.getElementById('current-price-display');
    if (priceElement) {
        priceElement.textContent = 'جاري التحميل...';
        priceElement.style.color = '#ff9800';
        priceElement.title = '';
    }

    // تمكين مؤقت للزر بينما نعيد الطلب (ليظهر أنه يعمل)
    const confirmBtn = Swal.getConfirmButton();
    if (confirmBtn) {
        confirmBtn.disabled = true;
        confirmBtn.textContent = 'جاري جلب السعر...';
        confirmBtn.style.backgroundColor = '#9e9e9e';
    }

    // مسح كاش السيرفر ثم جلب السعر
    jQuery.ajax({
        url: '<?php echo admin_url("admin-ajax.php"); ?>',
        method: 'POST',
        data: { action: 'st_clear_sol_price_cache' }
    }).always(function() {
        fetchSolPriceForSwap();
    });
};


function confirmSwapTransaction(swapData) {
    Swal.fire({
        title: 'تأكيد تحويل USDT → SOL',
        html: `
            <div style="text-align:right;direction:rtl;padding:12px">
                <div style="background:#f8f5ff;padding:16px;border-radius:12px;margin-bottom:12px">
                    <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px dashed #ddd">
                        <span style="font-weight:bold">المبلغ (USDT)</span>
                        <span style="color:#d32f2f;font-weight:bold">${(swapData.usdtAmount || 0).toFixed(4)} USDT</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px dashed #ddd">
                        <span style="font-weight:bold">الكمية (SOL)</span>
                        <span style="color:#2e7d32;font-weight:bold">${(swapData.solAmount || 0).toFixed(6)} SOL</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;padding:8px 0">
                        <span style="font-weight:bold">سعر الصرف</span>
                        <span style="color:#1976d2;font-weight:bold">1 SOL = ${(swapData.solPrice || 0).toFixed(4)} USDT</span>
                    </div>
                </div>
                <div style="background:#ffebee;padding:12px;border-radius:10px;border-right:4px solid #f44336;color:#c62828">
                    <i class="fas fa-exclamation-triangle" style="margin-left:8px"></i>
                    <strong>تنبيه:</strong> العملية غير قابلة للإلغاء بعد التأكيد.
                </div>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'تأكيد التحويل',
        cancelButtonText: 'رجوع',
        reverseButtons: true,
        heightAuto: false,
        customClass: {
            popup: 'swap-confirm-modal',
            htmlContainer: 'swap-confirm-html'
        },
        didOpen: (popup) => {
            const html = popup.querySelector('.swal2-html-container');
            if (html) {
                html.style.maxHeight = '260px';
                html.style.overflowY = 'auto';
                html.style.padding = '0';
            }
            popup.style.maxHeight = '420px';
            popup.style.overflow = 'hidden';
        }
    }).then((res) => {
        if (!res.isConfirmed) return;

        // التحقق من الرصيد الحقيقي قبل التنفيذ
        Swal.fire({
            title: 'تحقق من الرصيد...',
            allowOutsideClick: false,
            showConfirmButton: false,
            heightAuto: false,
            width: '340px',
            didOpen: () => { Swal.showLoading(); }
        });

        jQuery.getJSON('<?php echo admin_url("admin-ajax.php"); ?>', { 
            action: 'st_get_user_balances', 
            t: Date.now() 
        })
        .done(function(bal){
            Swal.close();

            if (bal && bal.success && bal.data && typeof bal.data.usdt === 'number') {
                if (parseFloat((swapData.usdtAmount || 0).toFixed(8)) > parseFloat((bal.data.usdt + 0.000001).toFixed(8))) {
                    Swal.fire({
                        icon: 'error',
                        title: 'رصيد USDT غير كاف',
                        html: `
                            <div style="text-align:right;direction:rtl;padding:12px">
                                <p style="margin:0;color:#d32f2f">الرصيد المتاح: ${bal.data.usdt.toFixed(5)} USDT</p>
                                <p style="margin:0;color:#666">المطلوب: ${(swapData.usdtAmount || 0).toFixed(5)} USDT</p>
                            </div>
                        `,
                        confirmButtonColor: '#d32f2f'
                    });
                    return;
                }
                executeSwapTransaction(swapData);
            } else {
                executeSwapTransaction(swapData);
            }
        })
        .fail(function(){
            Swal.close();
            executeSwapTransaction(swapData);
        });
    });
}



function executeSwapTransaction(swapData) {
    isSwapProcessing = true;

    // نافذة التحميل (spinner واحد فقط)
    Swal.fire({
        title: 'جاري التحويل...',
        html: `
            <div style="text-align:center;padding:16px">
                <p style="margin:0;font-size:15px;color:#555">يرجى الانتظار حتى يكتمل التحويل</p>
                <div style="margin-top:12px;font-size:13px;color:#666">
                    <div>${(swapData.usdtAmount || 0).toFixed(4)} USDT</div>
                    <div>${(swapData.solAmount || 0).toFixed(6)} SOL</div>
            </div>
        `,
        allowOutsideClick: false,
        showConfirmButton: false,
        heightAuto: false,
        width: '360px',
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {
        jQuery.ajax({
            url: '<?php echo admin_url("admin-ajax.php"); ?>',
            method: 'POST',
            data: {
                action: 'processusdttosolswap',
                usdtamount: parseFloat((swapData.usdtAmount || 0).toFixed(8)),
                solamount: parseFloat((swapData.solAmount || 0).toFixed(8)),
                solprice: parseFloat((swapData.solPrice || 0).toFixed(8)),
                ajaxnonce: '<?php echo wp_create_nonce("usdtsolswapnonce"); ?>'
            },
            dataType: 'json',
            timeout: 25000
        })
        .done(function (response) {
            isSwapProcessing = false;
            Swal.close();

            if (response && response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'تم التحويل',
                    html: `
                        <div style="text-align:right;direction:rtl;padding:12px">
                            <p style="margin:0;color:#2e7d32">تم خصم ${(swapData.usdtAmount || 0).toFixed(4)} USDT</p>
                            <p style="margin:0;color:#1976d2">وتم إضافة ${(response.data.swap_details.sol_amount || 0).toFixed(6)} SOL</p>
                        </div>
                    `,
                    confirmButtonText: 'حسناً',
                    confirmButtonColor: '#4caf50'
                }).then(function () {
                    //  هنا يتم إعادة تحميل الصفحة لضمان تحديث كل الأرصدة.
                    window.location.reload();
                });
            } else {
                var msg = (response && response.data && response.data.message) ? response.data.message : 'حدث خطأ غير متوقع.';
                Swal.fire({
                    icon: 'error',
                    title: 'فشل التحويل',
                    text: msg,
                    confirmButtonColor: '#d32f2f'
                });
            }
        })
        .fail(function (xhr, status) {
            isSwapProcessing = false;
            Swal.close();

            var msg = 'تعذر تنفيذ التحويل.';
            if (status === 'timeout') {
                msg = 'انتهت مهلة الطلب.';
            } else if (xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                msg = xhr.responseJSON.data.message;
            }

            Swal.fire({
                icon: 'error',
                title: 'فشل التحويل',
                text: msg,
                confirmButtonColor: '#d32f2f'
            });
        });
    } catch (e) {
        isSwapProcessing = false;
        Swal.close();
        Swal.fire({
            icon: 'error',
            title: 'فشل التحويل',
            text: 'حدث خطأ بالواجهة (JS).',
            confirmButtonColor: '#d32f2f'
        });
    }
}
    
    </script>
    <?php
    return ob_get_clean();
}
