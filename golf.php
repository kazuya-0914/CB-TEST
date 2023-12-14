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

    // SQL文
    $sql = $wpdb->prepare("SELECT status, scheduleUnixTime FROM $table_name WHERE emails = %s",  $user_email);

    // SQL文の結果を出力
    $results = $wpdb->get_results($sql, OBJECT);

    // SQL文の結果から条件分岐
    foreach ($results as $value) {
      /* 対象のメールアドレスのユーザーが承認済みでかつ
         現在の時刻が予約時間 + 90分より前だった場合 */
      if ($value->status === 'approved' && $value->scheduleUnixTime < time() + 90) {
          echo "<div>{$last_name}{$first_name}さんは現在予約済みです</div>";
          // ショートコードを実行して一切予約が出来ないカレンダーを表示
          return do_shortcode("[booking_package id=2]");
      } else {
          // それぞれの権限のサービスIDを割り当て
          $service_ids = array(
              'vip' => 1,  // VIPのサービスID
              'regular' => 2,  // レギュラーのサービスID
              '' => 3, // アーリーバードのサービスID
              '' => 4, // レイトナイトのサービスID
              '' => 5,  // ウィークデーのサービスID
              '' => 6, // ウィークデーナイトのサービスID
              '' => 7, // ナイト & ウイークエンドのサービスID
              '' => 8, // マンスリー２のサービスID
              '' => 9, // マンスリー４のサービスID
          );

          // 現在の権限に対応するサービスIDを取得
          $service_id = isset($service_ids[$user_roles]) ? $service_ids[$user_roles] : 2;

          // ショートコードを実行してサービスを表示
          return do_shortcode("[booking_package id=1 services={$service_id}]");
      }
    }
}
?>