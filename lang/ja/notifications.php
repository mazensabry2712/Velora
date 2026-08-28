<?php
return [
 'appointment_booked'=>['subject'=>'予約確認','greeting'=>':name さん、こんにちは。','message'=>'予約が正常に完了しました。','details'=>'予約詳細：','date'=>'日付：:date','time'=>'時間：:time','tenant'=>'クリニック：:tenant','footer'=>'ご利用いただきありがとうございます。','sms'=>':name さん、:tenant で :date :time に予約されています。'],
 'appointment_reminder'=>['subject'=>'予約リマインダー','greeting'=>':name さん、こんにちは。','message'=>'次回の予約についてのお知らせです。','details'=>'予約詳細：','date'=>'日付：:date','time'=>'時間：:time','tenant'=>'クリニック：:tenant','footer'=>'ご来院をお待ちしています。','sms'=>'リマインダー：:tenant で :date :time に予約があります。'],
 'appointment_cancelled'=>['subject'=>'予約キャンセル','greeting'=>':name さん、こんにちは。','message'=>'予約がキャンセルされました。','details'=>'キャンセルされた予約の詳細：','date'=>'日付：:date','time'=>'時間：:time','tenant'=>'クリニック：:tenant','footer'=>'いつでも別の予約を取ることができます。','sms'=>':tenant の :date :time の予約はキャンセルされました。'],
 'appointment_confirmed'=>['subject'=>'予約確定','greeting'=>':name さん、こんにちは。','message'=>'予約が確定しました。','details'=>'予約詳細：','date'=>'日付：:date','time'=>'時間：:time','tenant'=>'クリニック：:tenant','footer'=>'ご来院をお待ちしています。','sms'=>':tenant の :date :time の予約が確定しました。'],
 'queue_next'=>['subject'=>'あなたの番です！','greeting'=>':name さん、こんにちは。','message'=>'あなたの番です。受付へお越しください。','queue_number'=>'整理番号：:number','footer'=>'お待ちいただきありがとうございます。','sms'=>':name さん、あなたの番です。整理番号：:number。受付へお越しください。'],
 'queue_position_update'=>['subject'=>'待ち順更新','greeting'=>':name さん、こんにちは。','message'=>'現在の待ち順をお知らせします。','queue_number'=>'整理番号：:number','position'=>'前にいる人数：:position','estimated_wait'=>'予想待ち時間：:time 分','footer'=>'お待ちいただきありがとうございます。','sms'=>'整理番号：:number、前に :position 人、予想時間：:time 分'],
 'queue_ready'=>['subject'=>'もうすぐ順番です','greeting'=>' :name さん、こんにちは。','message'=>'まもなく順番です。ご準備ください。','queue_number'=>'整理番号：:number','position'=>'前にいるのは1人だけです','footer'=>'お待ちいただきありがとうございます。','sms'=>'ご準備ください。整理番号：:number、前にいるのは1人だけです。'],
 'queue_skipped'=>['subject'=>'順番がスキップされました','greeting'=>' :name さん、こんにちは。','message'=>'不在のため順番がスキップされました。','queue_number'=>'整理番号：:number','footer'=>'受付で新しい番号をお取りください。','sms'=>'整理番号 :number は不在のためスキップされました。新しい番号を取得できます。'],
 'view_details'=>'詳細を見る','thank_you'=>'ご利用いただきありがとうございます','regards'=>'よろしくお願いいたします',
];
