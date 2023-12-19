<?php
/***************************************************
 * :: Booking Package 専用ショートコード
 ***************************************************/

// 専用ショートコードを作成
add_shortcode('roles_service', 'roles_service_shortcode');

// 専用ショートコードの実行内容
function roles_service_shortcode($atts) {
    // タイムゾーンを日本に設定
    date_default_timezone_set('Asia/Tokyo');

    // カレンダー出力のための初期値
    $output = '';

    // 現在ログイン中のユーザーIDを取得
    $user_id = get_current_user_id();

    // ユーザーの情報を取得
    $user_info = get_userdata($user_id);

    // ユーザーの各情報を取得
    $first_name = $user_info->first_name; // お名前
    $last_name = $user_info->last_name; // 苗字
    $user_email = $user_info->user_email; //メールアドレス
    $user_roles = $user_info->roles; // 全ての権限の配列（「購読者 + プレムアム」など）

    // もし一つ目の権限が購読者であれば二つ目の権限を出力
    $user_role = $user_roles[0] === 'subscriber' ? $user_roles[1] : $user_roles[0];

    /* --- 唯一のユニーク値であるメールアドレス + 予約が承認済みのデータを検索 --- */
    // $wpdbクエリ
    global $wpdb;

    // テーブル名
    $table_name = $wpdb->prefix . 'booking_package_booked_customers';

    // Booking Packageのメールアドレス形式に合わせる
    $user_email_sql = '["' . $user_email . '"]';

    // SQL文
    $sql = $wpdb->prepare("SELECT scheduleUnixTime FROM $table_name
        WHERE status = 'approved'
        AND emails = %s",  $user_email_sql);

    // SQL文の結果を出力
    $results = $wpdb->get_results($sql, OBJECT);

    /* SQL文の結果から予約NG判定 */
    /* 予約データが存在かつ、プレミアム会員以外であれば予約NGチェック */
    if (!empty($results) && $user_role !== 'premium') {

        /* --- 【NG1】 マンスリー２、マンスリー4の予約回数制限--- */
        if ($user_role === 'monthly2' || $user_role === 'monthly4') {
            // 予約回数カウント
            $i = 0;

            // Max予約回数
            $max_num = $user_role === 'monthly2' ? 2 : 4;
        
            // 予約データチェック
            foreach ($results as $value) {
                // 今月の予約であれば予約回数カウントプラス
                if (date("Y/m", $value->scheduleUnixTime) === date("Y/m", time())) {
                    $i++;
                }

                // Max予約回数を超えたら予約不可
                if ($i >= $max_num) {
                    $output .= "<p class=\"warning-statement\">{$last_name}{$first_name}さんは今月の予約回数を超えました</p>";
                    // ショートコードを実行して予約不可カレンダーを表示
                    $output .= do_shortcode("[booking_package id=1 services=25]");
                    return $output;
                }
            }
            $output .=  "<p class=\"times-text\">{$last_name}{$first_name}さんは今月{$i}回予約をしています</p>";
        }

        // 予約データチェック
        foreach ($results as $value) {
            
            /* --- 【NG2】現在の時刻が予約時間 + 90分より前だった場合 --- */
            if ($value->scheduleUnixTime + 90 * 60 > time()) {
                $output .= "<p class=\"warning-statement\">{$last_name}{$first_name}さんは現在予約済みです</p>";
                // ショートコードを実行して予約不可カレンダーを表示
                $output .= do_shortcode("[booking_package id=1 services=25]");
                return $output;
            }
    
            /* --- 【NG3】本日予約済みだった場合 --- */
            if (date("Y/m/d", $value->scheduleUnixTime) === date("Y/m/d", time())) {
                $output .= "<p class=\"warning-statement\">{$last_name}{$first_name}さんは本日予約済みです</p>";
                // ショートコードを実行して予約不可カレンダーを表示
                $output .= do_shortcode("[booking_package id=1 services=25]");
                return $output;
            }
        }
    }
        
    /* --- 全ての予約不可条件をクリアした場合は予約OK --- */
    // それぞれの権限のサービスIDを割り当て
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
    $service_id = isset($service_ids[$user_role]) ? $service_ids[$user_role] : 25;

    // ショートコードを実行してサービスを表示
    $output .= do_shortcode("[booking_package id=1 services={$service_id}]");
    return $output;
}
?>