<?php
return [
 'appointment_booked'=>['subject'=>'예약 확인','greeting'=>':name님, 안녕하세요.','message'=>'예약이 성공적으로 완료되었습니다!','details'=>'예약 상세:','date'=>'날짜: :date','time'=>'시간: :time','tenant'=>'클리닉: :tenant','footer'=>'서비스를 이용해 주셔서 감사합니다!','sms'=>':name님, :tenant에서 :date :time에 예약되었습니다.'],
 'appointment_reminder'=>['subject'=>'예약 알림','greeting'=>':name님, 안녕하세요.','message'=>'다가오는 예약에 대한 알림입니다!','details'=>'예약 상세:','date'=>'날짜: :date','time'=>'시간: :time','tenant'=>'클리닉: :tenant','footer'=>'방문을 기다리고 있습니다!','sms'=>'알림: :tenant에서 :date :time에 예약이 있습니다.'],
 'appointment_cancelled'=>['subject'=>'예약 취소','greeting'=>':name님, 안녕하세요.','message'=>'예약이 취소되었습니다.','details'=>'취소된 예약 상세:','date'=>'날짜: :date','time'=>'시간: :time','tenant'=>'클리닉: :tenant','footer'=>'언제든지 다른 예약을 할 수 있습니다.','sms'=>':tenant의 :date :time 예약이 취소되었습니다.'],
 'appointment_confirmed'=>['subject'=>'예약 확정','greeting'=>':name님, 안녕하세요.','message'=>'예약이 확정되었습니다!','details'=>'예약 상세:','date'=>'날짜: :date','time'=>'시간: :time','tenant'=>'클리닉: :tenant','footer'=>'방문을 기다리고 있습니다!','sms'=>':tenant의 :date :time 예약이 확정되었습니다.'],
 'queue_next'=>['subject'=>'지금 차례입니다!','greeting'=>':name님, 안녕하세요.','message'=>'지금 차례입니다! 안내 데스크로 이동해 주세요.','queue_number'=>'대기 번호: :number','footer'=>'기다려 주셔서 감사합니다!','sms'=>':name님, 지금 차례입니다! 대기 번호: :number. 안내 데스크로 이동해 주세요.'],
 'queue_position_update'=>['subject'=>'대기 순서 업데이트','greeting'=>':name님, 안녕하세요.','message'=>'현재 대기 순서를 알려드립니다:','queue_number'=>'대기 번호: :number','position'=>'앞에 있는 사람: :position명','estimated_wait'=>'예상 대기 시간: :time분','footer'=>'기다려 주셔서 감사합니다!','sms'=>'대기 번호: :number, 앞에 :position명, 예상 시간: :time분'],
 'queue_ready'=>['subject'=>'곧 차례입니다','greeting'=>':name님, 안녕하세요.','message'=>'곧 차례입니다. 준비해 주세요!','queue_number'=>'대기 번호: :number','position'=>'앞에 1명만 있습니다','footer'=>'기다려 주셔서 감사합니다!','sms'=>'준비해 주세요! 대기 번호: :number, 앞에 1명만 있습니다.'],
 'queue_skipped'=>['subject'=>'차례가 건너뛰어졌습니다','greeting'=>':name님, 안녕하세요.','message'=>'부재로 인해 차례가 건너뛰어졌습니다.','queue_number'=>'대기 번호: :number','footer'=>'안내 데스크에서 새 번호를 받을 수 있습니다.','sms'=>'번호 :number 차례가 부재로 건너뛰어졌습니다. 새 번호를 받을 수 있습니다.'],
 'view_details'=>'상세 보기','thank_you'=>'서비스를 이용해 주셔서 감사합니다','regards'=>'감사합니다',
];
