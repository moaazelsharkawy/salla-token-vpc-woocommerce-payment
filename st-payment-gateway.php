<?php
/**
 * Plugin Name: ST Virtual Cards Gateway
 * Description: بوابة دفع ST Virtual Cards لـ WooCommerce تستدعي REST API في الموقع الرئيسي.
 * Version:     1.0
 * Author:      Salla Developer
 * Text Domain: st-vpc-gateway
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


add_action( 'wp_enqueue_scripts', 'st_vpc_enqueue_styles' );
function st_vpc_enqueue_styles() {
    // إذا أردت تحميله فقط بصفحة الخروج، تفعل الشرط التالي:
    if ( function_exists( 'is_checkout' ) && is_checkout() ) {
        wp_enqueue_style(
            'st-vpc-styles',
            plugins_url( 'assets/css/st-vpc.css', __FILE__ ),
            array(),
            '2.5',
            'all'
        );
    }
}

/**
 * 1) إضافة العملة الجديدة إلى WooCommerce
 */
add_filter( 'woocommerce_currencies', 'st_vpc_add_salla_token_currency' );
function st_vpc_add_salla_token_currency( $currencies ) {
    $currencies['ST'] = __( 'Salla Token', 'st-vpc-gateway' );
    return $currencies;
}

/**
 * 2) تعيين رمز العملة
 */
add_filter( 'woocommerce_currency_symbol', 'st_vpc_add_salla_token_symbol', 10, 2 );
function st_vpc_add_salla_token_symbol( $symbol, $currency ) {
    if ( 'ST' === $currency ) {
        $symbol = 'ST';
    }
    return $symbol;
}

/**
 * 3) تسجيل بوابة الدفع لدى WooCommerce
 */
add_filter( 'woocommerce_payment_gateways', 'st_vpc_register_gateway' );
function st_vpc_register_gateway( $methods ) {
    $methods[] = 'WC_Gateway_ST_VPC';
    return $methods;
}

/**
 * 4) تحميل الكلاس بعد تحميل جميع الإضافات
 */
add_action( 'plugins_loaded', 'st_vpc_init_gateway' );
function st_vpc_init_gateway() {

    class WC_Gateway_ST_VPC extends WC_Payment_Gateway {

        /**
         * @var string رابط REST API لمعالجة الدفع.
         */
        protected $api_endpoint;
        /**
         * @var string رابط REST API لمعالجة الاسترداد.
         */
        protected $refund_endpoint;
        /**
         * @var string مفتاح API لتوثيق الطلبات.
         */
        protected $api_key;

        /**
         * Constructor: إعداد البوابة
         */
        public function __construct() {
            $this->id                 = 'st_vpc';
            $this->method_title       = 'ST Virtual Cards';
            $this->method_description = 'يقبل المدفوعات باستخدام بطاقات ST الافتراضية عبر REST API مركزي.';
            $this->has_fields         = true;
            $this->supports           = array( 'products', 'refunds' );

            // إعداد الإعدادات
            $this->init_form_fields();
            $this->init_settings();

            // خيارات الإدارة
            $this->title           = $this->get_option( 'title', 'ST Virtual Cards' );
            $this->description     = $this->get_option( 'description', 'ادفع باستخدام بطاقات ST الافتراضية' );
            $this->api_endpoint    = $this->get_option( 'api_endpoint' );
            $this->refund_endpoint = $this->get_option( 'refund_endpoint' );
            $this->api_key         = $this->get_option( 'api_key' );
            $this->enabled         = $this->get_option( 'enabled' );

            // حفظ الإعدادات
            add_action(
                'woocommerce_update_options_payment_gateways_' . $this->id,
                array( $this, 'process_admin_options' )
            );

            // رسائل تحذيرية
            add_action( 'admin_notices', array( $this, 'admin_notices' ) );
        }

        /**
         * عرض رسائل تحذيرية في لوحة التحكم
         */
        public function admin_notices() {
            if ( 'yes' !== $this->enabled ) {
                return;
            }
            if ( empty( $this->api_endpoint ) || empty( $this->refund_endpoint ) || empty( $this->api_key ) ) {
                echo '<div class="notice notice-warning is-dismissible"><p>'
                     . sprintf(
                         __( 'بوابة الدفع "%s" مفعلة، ولكن بعض الإعدادات الأساسية (API Endpoints أو API Key) مفقودة. يرجى مراجعة <a href="%s">إعدادات البوابة</a>.', 'woocommerce' ),
                         $this->method_title,
                         admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $this->id )
                     )
                     . '</p></div>';
            }
        }

        /**
         * حقول الإعدادات في لوحة الإدارة
         */
        public function init_form_fields() {
            $this->form_fields = array(
                'enabled'         => array(
                    'title'   => 'تفعيل/تعطيل',
                    'type'    => 'checkbox',
                    'label'   => 'تفعيل دفع ST Virtual Cards',
                    'default' => 'no',
                ),
                'title'           => array(
                    'title'   => 'عنوان الدفع',
                    'type'    => 'text',
                    'default' => 'ST Virtual Cards',
                ),
                'description'     => array(
                    'title'   => 'وصف الدفع',
                    'type'    => 'textarea',
                    'default' => 'ادفع باستخدام بطاقات ST الافتراضية',
                ),
                'api_endpoint'    => array(
                    'title'       => 'REST Endpoint (الدفع)',
                    'type'        => 'url',
                    'placeholder' => 'https://your-central-site.com/wp-json/st-vpc/v1/transactions',
                    'description' => 'رابط الـ REST API الكامل لمعالجة الدفع في الموقع المركزي.',
                    'desc_tip'    => true,
                ),
                'refund_endpoint' => array(
                    'title'       => 'REST Endpoint (الاسترداد)',
                    'type'        => 'url',
                    'placeholder' => 'https://your-central-site.com/wp-json/st-vpc/v1/refund',
                    'description' => 'رابط الـ REST API الكامل لمعالجة الاسترداد في الموقع المركزي.',
                    'desc_tip'    => true,
                ),
                'api_key'         => array(
                    'title'       => 'API Key',
                    'type'        => 'password',
                    'description' => 'مفتاح API الذي تم إنشاؤه في الموقع المركزي لتوثيق هذا الموقع الفرعي.',
                    'desc_tip'    => true,
                ),
                
// … الحقول السابقة (enabled, title, description, api_endpoint, refund_endpoint, api_key)
        'st_rate' => array(
            'title'             => __( 'سعر Salla Token مقابل العملة المحلية', 'st-vpc-gateway' ),
            'type'              => 'number',
            'description'       => __( 'إذا تركتها فارغة سيتم قبول الدفع فقط بعملة ST. إذا أدخلت قيمة، سيُحوّل إجمالي الطلب إلى ST بالقسمة على هذه القيمة.', 'st-vpc-gateway' ),
            'desc_tip'          => true,
            'default'           => '',
            'custom_attributes' => array(
                'step' => '0.01',
                'min'  => '0',
            ),
        ),
    

                
                
            );
        }

        /**
         * عرض حقول الدفع في صفحة الخروج
*/

public function payment_fields() {
    // عرض الوصف إن وُجد
    if ( $this->description ) {
        echo wpautop( wp_kses_post( $this->description ) );
    }

    // nonce للأمان
    wp_nonce_field( 'st_vpc_process_payment_nonce', 'st_vpc_payment_nonce' );

    // حساب إجمالي السلة وسعر ST
    $cart_total = WC()->cart->total;
    $st_rate    = floatval( $this->get_option( 'st_rate', '' ) );

    // العملة الحالية في ووكومرس
    $current_currency = get_woocommerce_currency();

    // إذا تم إدخال سعر ST، نعرض المبلغ المطلوب بوحدة ST
    if ( $st_rate > 0 ) {
        $amount_st = round( $cart_total / $st_rate, 2 );
        echo '<p class="st-total" style="margin-bottom:1.5rem;font-weight:600;">';
        echo sprintf(
            /* translators: 1: amount in ST, 2: equivalent in local currency */
            __( 'المبلغ المطلوب: %1$s ST (يعادل %2$s)', 'st-vpc-gateway' ),
            esc_html( $amount_st ),
            wc_price( $cart_total )
        );
        echo '</p>';
    }

    // شرط تجميد الحقول: إذا ترك البائع حقل سعر ST فارغاً ولم يختَر عملة ST في الإعدادات
    $freeze_fields = empty( $this->get_option( 'st_rate', '' ) ) && ( 'ST' !== $current_currency );

    // رسالة تحذير عند تجميد الحقول (اختياري)
    if ( $freeze_fields ) {
        echo '<p class="woocommerce-info" style="margin-bottom:1rem;">';
        echo esc_html__( 'بوابة ST Virtual Cards مفعّلة للعملة ST فقط. يرجى في الإعدادات إدخال سعر ST أو اختيار العملة ST كي تتمكّن من إدخال بيانات البطاقة.', 'st-vpc-gateway' );
        echo '</p>';
    }

    // حقول إدخال بيانات البطاقة
    $disabled_attr = $freeze_fields ? 'disabled readonly style="background: #f5f5f5; cursor: not-allowed;"' : '';

    echo '<fieldset id="wc-' . esc_attr( $this->id ) . '-cc-form" class="wc-credit-card-form wc-payment-form">';

        // رقم البطاقة
        echo '<p class="form-row form-row-wide">';
            echo '<label for="st_vpc_card_number">'
                 . esc_html__( 'رقم البطاقة', 'woocommerce' )
                 . ' <span class="required">*</span></label>';
            echo '<input
                    id="st_vpc_card_number"
                    name="st_vpc_card_number"
                    class="input-text wc-credit-card-form-card-number"
                    type="text"
                    maxlength="19"
                    autocomplete="cc-number"
                    placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;"
                    ' . $disabled_attr . '
                    required
                  />';
        echo '</p>';

        // رمز الأمان CVV
        echo '<p class="form-row form-row-wide">';
            echo '<label for="st_vpc_cvv">'
                 . esc_html__( 'رمز الأمان (CVV)', 'woocommerce' )
                 . ' <span class="required">*</span></label>';
            echo '<input
                    id="st_vpc_cvv"
                    name="st_vpc_cvv"
                    class="input-text wc-credit-card-form-cvc"
                    type="password"
                    maxlength="4"
                    autocomplete="off"
                    placeholder="' . esc_attr__( 'CVV', 'woocommerce' ) . '"
                    ' . $disabled_attr . '
                    required
                  />';
        echo '</p>';

        echo '<div class="clear"></div>';
    echo '</fieldset>';
}


        /**
         * التحقق من الحقول قبل المعالجة
         */
        public function validate_fields() {
    // التحقق من الـ nonce للأمان
    if (
        ! isset( $_POST['st_vpc_payment_nonce'] )
        || ! wp_verify_nonce(
            sanitize_text_field( wp_unslash( $_POST['st_vpc_payment_nonce'] ) ),
            'st_vpc_process_payment_nonce'
        )
    ) {
        wc_add_notice( __( 'خطأ في الدفع: فشل التحقق الأمني. يرجى المحاولة مرة أخرى.', 'woocommerce' ), 'error' );
        return false;
    }

    // جلب القيم وتنظيفها
    $card_number = isset( $_POST['st_vpc_card_number'] )
        ? str_replace( array( ' ', '-' ), '', sanitize_text_field( $_POST['st_vpc_card_number'] ) )
        : '';
    $cvv = isset( $_POST['st_vpc_cvv'] )
        ? sanitize_text_field( $_POST['st_vpc_cvv'] )
        : '';

    // التحقق من رقم البطاقة
    if ( empty( $card_number ) ) {
        wc_add_notice( __( 'يرجى إدخال رقم البطاقة.', 'woocommerce' ), 'error' );
        return false;
    }

    // التحقق من رمز الأمان (CVV)
    if ( empty( $cvv ) ) {
        wc_add_notice( __( 'يرجى إدخال رمز الأمان للبطاقة (CVV).', 'woocommerce' ), 'error' );
        return false;
    }

    return true;
}


private function remote_post_with_retries( $url, $args, $max_attempts = 3, $delay_seconds = 1 ) {
    for ( $i = 1; $i <= $max_attempts; $i++ ) {
        $response = wp_remote_post( $url, $args );
        if ( ! is_wp_error( $response ) ) {
            return $response;
        }
        // إذا كان الخطأ من نوع “couldn’t connect” جرب مرة أخرى بعد تأخير
        $code = $response->get_error_code();
        if ( $code !== CURLE_COULDNT_CONNECT ) {
            break;
        }
        sleep( $delay_seconds );
    }
    return $response;
}



/**
 * تنفيذ الدفع عند تأكيد الطلب (مع Idempotency-Key و retry logic).
 */
public function process_payment( $order_id ) {
    if ( empty( $this->api_endpoint ) || empty( $this->api_key ) ) {
        wc_add_notice( __( 'Payment gateway is not configured correctly. Please contact support.', 'woocommerce' ), 'error' );
        return [ 'result' => 'failure' ];
    }

    $order       = wc_get_order( $order_id );
    $card_number = isset( $_POST['st_vpc_card_number'] )
        ? str_replace( [ ' ', '-' ], '', sanitize_text_field( wp_unslash( $_POST['st_vpc_card_number'] ) ) )
        : '';
    $cvv         = isset( $_POST['st_vpc_cvv'] )
        ? sanitize_text_field( wp_unslash( $_POST['st_vpc_cvv'] ) )
        : '';
    $amount      = $order->get_total();

    if ( empty( $card_number ) || empty( $cvv ) ) {
        wc_add_notice( __( 'Card number and CVV are required.', 'woocommerce' ), 'error' );
        return [ 'result' => 'failure' ];
    }

    // 1) Generate and store idempotency key
    $idempotency_key = $order_id . '_' . wp_generate_uuid4();
    update_post_meta( $order_id, '_st_vpc_idempotency_key', $idempotency_key );

    // 2) Prepare args with Idempotency-Key header
    $args = [
        'headers'   => [
            'Content-Type'      => 'application/json',
            'X-API-KEY'         => $this->api_key,
            'Idempotency-Key'   => $idempotency_key,
            'Expect'            => '',
        ],
        'body'      => wp_json_encode( [
            'card_number' => $card_number,
            'cvv'         => $cvv,
            'amount'      => $amount,
            'order_id'    => $order_id,
        ] ),
        'timeout'   => 45,
        'sslverify' => true,
    ];

    // 3) Send with retries
    $response = $this->remote_post_with_retries( $this->api_endpoint, $args, 3, 1 );

    // 4) Handle connection error
    if ( is_wp_error( $response ) ) {
        $error_message = $response->get_error_message();
        wc_add_notice( sprintf( __( 'Payment error: %s', 'woocommerce' ), $error_message ), 'error' );
        $order->add_order_note( sprintf( __( 'ST VPC Payment Failed (Connection Error): %s', 'woocommerce' ), $error_message ) );
        return [ 'result' => 'failure' ];
    }

    // 5) Parse response
    $http_code = wp_remote_retrieve_response_code( $response );
    $body      = wp_remote_retrieve_body( $response );
    $data      = json_decode( $body, true );

    // 6) Success?
    if ( $http_code >= 200 && $http_code < 300
         && ! empty( $data['transaction_id'] )
         && isset( $data['status'] ) && 'frozen' === $data['status'] ) {

        $order->payment_complete( $data['transaction_id'] );
        $order->update_status(
            'processing',
            sprintf( __( 'Payment successful via ST Virtual Card. Transaction ID: %s', 'woocommerce' ), $data['transaction_id'] )
        );
        $order->add_order_note(
            sprintf( __( 'ST VPC Payment Successful. Transaction ID: %s', 'woocommerce' ), $data['transaction_id'] )
        );
        WC()->cart->empty_cart();

        return [
            'result'   => 'success',
            'redirect' => $this->get_return_url( $order ),
        ];
    }

    // 7) API-level failure
    $api_error_message = ! empty( $data['message'] )
        ? sanitize_text_field( $data['message'] )
        : __( 'Payment failed.', 'woocommerce' );
    wc_add_notice( $api_error_message, 'error' );
    $order->add_order_note(
        sprintf(
            __( 'ST VPC Payment Failed (API Response): %s - Response Body: %s', 'woocommerce' ),
            $api_error_message,
            $body
        )
    );
    return [ 'result' => 'failure' ];
}

        /**
         * تنفيذ الاسترداد (Refund)
         */
        /**
 * تنفيذ الاسترداد (Refund) مع Idempotency-Key لمنع التكرار.
 */
public function process_refund( $order_id, $amount = null, $reason = '' ) {
    if ( empty( $this->refund_endpoint ) || empty( $this->api_key ) ) {
        return new WP_Error( 'config_error', __( 'Refund Error: Gateway refund endpoint or API key is not configured.', 'woocommerce' ) );
    }

    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        return new WP_Error( 'invalid_order', __( 'Refund Error: Invalid order ID.', 'woocommerce' ) );
    }
    if ( is_null( $amount ) || floatval( $amount ) <= 0 ) {
        return new WP_Error( 'invalid_amount', __( 'Refund Error: Invalid refund amount.', 'woocommerce' ) );
    }

    // 1) Generate & store idempotency key for refund
    $idempotency_key = $order_id . '_refund_' . wp_generate_uuid4();
    update_post_meta( $order_id, '_st_vpc_refund_idempotency_key', $idempotency_key );

    // 2) Prepare request args
    $args = [
        'headers' => [
            'Content-Type'      => 'application/json',
            'X-API-KEY'         => $this->api_key,
            'Idempotency-Key'   => $idempotency_key,
        ],
        'body'    => wp_json_encode( [
            'order_id' => $order_id,
            'amount'   => floatval( $amount ),
        ] ),
        'timeout' => 45,
    ];

    // 3) Send refund request (can also use retries if desired)
    $response = wp_remote_post( $this->refund_endpoint, $args );

    if ( is_wp_error( $response ) ) {
        $error_message = $response->get_error_message();
        $order->add_order_note(
            sprintf( __( 'ST VPC Refund Failed (Connection Error): %s', 'woocommerce' ), $error_message )
        );
        return new WP_Error( 'api_connection_error', sprintf( __( 'Refund API Connection Error: %s', 'woocommerce' ), $error_message ) );
    }

    $http_code = wp_remote_retrieve_response_code( $response );
    $body      = wp_remote_retrieve_body( $response );
    $data      = json_decode( $body, true );

    if ( $http_code >= 200 && $http_code < 300
         && ! empty( $data['status'] ) && 'success' === $data['status']
         && ! empty( $data['refund_txn_id'] ) ) {

        // Add order note
        $refund_message = sprintf(
            __( 'Refunded %1$s via ST VPC. Refund Transaction ID: %2$s', 'woocommerce' ),
            wc_price( $amount ),
            $data['refund_txn_id']
        );
        if ( $reason ) {
            $refund_message .= ' ' . sprintf( __( 'Reason: %s', 'woocommerce' ), esc_html( $reason ) );
        }
        $order->add_order_note( $refund_message );

        // Optionally send merchant notification
        if ( file_exists( plugin_dir_path( __FILE__ ) . 'st-mining2/notifications.php' ) ) {
            require_once plugin_dir_path( __FILE__ ) . 'st-mining2/notifications.php';
            if ( function_exists( 'add_notification' ) ) {
                $message = "تم استرداد مبلغ {$amount} للطلب #{$order_id}.";
                add_notification( $order->get_customer_id(), $message );
            }
        }

        return true;
    }

    // API-error
    $api_error_message = ! empty( $data['message'] )
        ? sanitize_text_field( $data['message'] )
        : sprintf( __( 'Refund failed. API Error code: %s', 'woocommerce' ), sanitize_text_field( $data['code'] ?? '' ) );
    $order->add_order_note(
        sprintf( __( 'ST VPC Refund Failed (API Response): %s - Response Body: %s', 'woocommerce' ), $api_error_message, $body )
    );
    return new WP_Error( 'api_refund_error', $api_error_message );
}

    } // نهاية الكلاس WC_Gateway_ST_VPC
} // نهاية دالة st_vpc_init_gateway()
