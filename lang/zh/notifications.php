<?php
return [
 'appointment_booked'=>['subject'=>'预约确认','greeting'=>'您好，:name：','message'=>'您的预约已成功完成！','details'=>'预约详情：','date'=>'日期：:date','time'=>'时间：:time','tenant'=>'诊所：:tenant','footer'=>'感谢您使用我们的服务！','sms'=>'您好，:name，您已预约 :tenant，时间为 :date :time'],
 'appointment_reminder'=>['subject'=>'预约提醒','greeting'=>'您好，:name：','message'=>'这是您即将到来的预约提醒！','details'=>'预约详情：','date'=>'日期：:date','time'=>'时间：:time','service'=>'服务：:service','staff'=>'专家：:staff','tenant'=>'诊所：:tenant','queue'=>'排队号码：:number','reference'=>'预约参考号：:reference','tracking'=>'跟踪您的预约：:url','footer'=>'期待您的到来！','sms'=>'提醒：您在 :tenant 的预约时间为 :date :time'],
 'appointment_cancelled'=>['subject'=>'预约已取消','greeting'=>'您好，:name：','message'=>'您的预约已被取消。','details'=>'已取消预约详情：','date'=>'日期：:date','time'=>'时间：:time','tenant'=>'诊所：:tenant','footer'=>'您可以随时重新预约。','sms'=>'您在 :tenant 于 :date :time 的预约已取消'],
 'appointment_confirmed'=>['subject'=>'预约已确认','greeting'=>'您好，:name：','message'=>'您的预约已确认！','details'=>'预约详情：','date'=>'日期：:date','time'=>'时间：:time','tenant'=>'诊所：:tenant','footer'=>'期待您的到来！','sms'=>'您在 :tenant 于 :date :time 的预约已确认'],
 'queue_next'=>['subject'=>'现在轮到您了！','greeting'=>'您好，:name：','message'=>'现在轮到您了！请立即前往服务台。','queue_number'=>'排队号码：:number','footer'=>'感谢您的等待！','sms'=>'您好，:name，现在轮到您了！排队号码：:number。请前往服务台。'],
 'queue_position_update'=>['subject'=>'排队位置更新','greeting'=>'您好，:name：','message'=>'您的排队位置已更新：','queue_number'=>'排队号码：:number','position'=>'您前面的人数：:position','estimated_wait'=>'预计等待时间：:time 分钟','footer'=>'感谢您的耐心等待！','sms'=>'号码：:number，前面还有 :position 人，预计等待 :time 分钟'],
 'queue_ready'=>['subject'=>'很快就轮到您','greeting'=>'您好，:name：','message'=>'请做好准备，很快就到您了！','queue_number'=>'排队号码：:number','position'=>'您前面只有 1 人','footer'=>'感谢您的等待！','sms'=>'请做好准备！号码：:number，您前面只有 1 人。'],
 'queue_skipped'=>['subject'=>'您的号码已过号','greeting'=>'您好，:name：','message'=>'由于您未到场，您的号码已过号。','queue_number'=>'排队号码：:number','footer'=>'您可以在服务台领取新的号码。','sms'=>'您的号码 :number 因未到场而过号。您可以领取新的排队号码。'],
 'view_details'=>'查看详情','thank_you'=>'感谢您使用我们的服务','regards'=>'此致敬礼',
];
