<?php 
add_shortcode('roles_service', 'roles_service_shortcode');
function roles_service_shortcode($atts) {
    // 現在ログイン中のユーザーIDを取得
    $user_id = get_current_user_id();

    // ユーザーの情報を取得
    $user_info = get_userdata($user_id);

    // ユーザーの各情報を取得
    $first_name = $user_info->first_name; // お名前
    $last_name = $user_info->last_name; // 苗字
    $user_email = $user_info->user_email; //メールアドレス
    $user_roles = $user_info->roles; // 権限

    // $wpdbクエリ
    global $wpdb;

    // テーブル名
    $table_name = $wpdb->prefix . 'booking_package_booked_customers';

    // SQL文（唯一のユニーク値であるメールアドレスで検索）
    $sql = $wpdb->prepare("SELECT status, scheduleUnixTime FROM $table_name WHERE emails = %s",  $user_email);

    // SQL文の結果を出力
    $results = $wpdb->get_results($sql, OBJECT);

    // SQL文の結果から条件分岐
    foreach ($results as $value) {
        /* --- ユーザーが承認済みでかつ、現在の時刻が予約時間 + 90分より前だった場合 --- */
        if ($value->status === 'approved' && $value->scheduleUnixTime + 90 > time()) {
            echo "<p>{$last_name}{$first_name}さんは現在予約済みです</p>";
            // ショートコードを実行して予約不可カレンダーを表示
            return do_shortcode("[booking_package id=1 services=25]");
        }

        /* --- ユーザーが承認済みでかつ、本日予約済みだった場合 --- */
        if ($value->status === 'approved' && date("Y/m/d", $value->scheduleUnixTime) === date("Y/m/d", time())) {
            echo "<p>{$last_name}{$first_name}さんは本日予約済みです</p>";
            // ショートコードを実行して予約不可カレンダーを表示
            return do_shortcode("[booking_package id=1 services=25]");
        }
        
        /* --- 全ての予約不可条件をクリアした場合は予約OK --- */
        // それぞれの権限のサービスIDを割り当て(アーリーバード以下は権限IDを検討中)
        $service_ids = array(
            'premium' => 1,  // プレミアムのサービスID
            'regular' => 2,  // レギュラーのサービスID
            'early-bird' => 3, // アーリーバードのサービスID
            'late-night' => 4, // レイトナイトのサービスID
            'weekday' => 5,  // ウィークデーのサービスID
            'weekday-night' => 6, // ウィークデーナイトのサービスID
            'night-weekend' => 7, // ナイト & ウイークエンドのサービスID
            'monthly2' => 8, // マンスリー２のサービスID
            'monthly4' => 9, // マンスリー４のサービスID
        );

        // 現在の権限に対応するサービスIDを取得(対応する権限がなければ予約不可カレンダーを表示)
            $service_id = isset($service_ids[$user_roles]) ? $service_ids[$user_roles] : 25;

        // ショートコードを実行してサービスを表示
        return do_shortcode("[booking_package id=1 services={$service_id}]");
        
    }
}
?>