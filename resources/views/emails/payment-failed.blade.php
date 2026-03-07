<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>فشل الدفع — Velora</title>
<style>
  body { font-family: 'Segoe UI', Tahoma, Geneva, sans-serif; background:#f8fafc; margin:0; padding:20px; direction:rtl; }
  .wrap { max-width:600px; margin:0 auto; background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,.08); }
  .header { background:linear-gradient(135deg,#dc2626,#b91c1c); padding:32px 40px; text-align:center; }
  .header h1 { color:#fff; margin:0; font-size:22px; }
  .header .icon { font-size:48px; margin-bottom:12px; display:block; }
  .body { padding:36px 40px; }
  .body p { color:#374151; line-height:1.7; margin:0 0 16px; font-size:15px; }
  .alert-box { background:#fef2f2; border:1px solid #fecaca; border-radius:8px; padding:20px 24px; margin:24px 0; }
  .alert-box p { margin:0; color:#991b1b; }
  .grace-countdown { text-align:center; background:#fff7ed; border:2px dashed #f59e0b; border-radius:10px; padding:20px; margin:24px 0; }
  .grace-countdown .days { font-size:48px; font-weight:700; color:#d97706; }
  .grace-countdown p { margin:4px 0; color:#92400e; font-size:14px; }
  .btn { display:block; width:fit-content; margin:28px auto 0; background:linear-gradient(135deg,#dc2626,#b91c1c);
         color:#fff; text-decoration:none; padding:14px 36px; border-radius:8px; font-size:16px; font-weight:600; text-align:center; }
  .footer { background:#f9fafb; padding:20px 40px; text-align:center; border-top:1px solid #e5e7eb; font-size:12px; color:#9ca3af; }
  .steps { list-style:none; padding:0; margin:16px 0; }
  .steps li { padding:8px 0; border-bottom:1px solid #f3f4f6; color:#4b5563; font-size:14px; }
  .steps li::before { content:'↴ '; color:#dc2626; }
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <span class="icon">⚠️</span>
    <h1>فشل تجديد اشتراكك</h1>
  </div>
  <div class="body">
    <p>مرحباً فريق <strong>{{ $businessName }}</strong>،</p>
    <p>
      تعذّر تجديد اشتراكك في Velora تلقائياً.
      لم يتم خصم أي مبلغ من حسابك، لكن تحتاج إلى تحديث بيانات الدفع للاستمرار في استخدام النظام.
    </p>

    <div class="alert-box">
      <p><strong>رقم الفاتورة:</strong> {{ $invoiceId }}</p>
    </div>

    <div class="grace-countdown">
      <div class="days">{{ $graceDays }}</div>
      <p><strong>أيام متبقية في فترة السماح</strong></p>
      <p>بعدها سيصبح الحساب للقراءة فقط حتى تحديث بيانات الدفع.</p>
    </div>

    <p><strong>ماذا تفعل الآن:</strong></p>
    <ul class="steps">
      <li>انقر على الرابط أدناه لفتح بوابة الفوترة</li>
      <li>قم بتحديث بطاقتك الائتمانية أو وسيلة الدفع</li>
      <li>ستُجدَّد المدفوعات تلقائياً بعد التحديث</li>
    </ul>

    <a href="{{ $billingPortalUrl }}" class="btn">تحديث بيانات الدفع →</a>

    <p style="margin-top:28px; font-size:13px; color:#9ca3af;">
      إذا كنت تعتقد أن هذا خطأ أو تحتاج مساعدة، تواصل معنا عبر الرد على هذا الإيميل.
    </p>
  </div>
  <div class="footer">
    <p>Velora — نظام إدارة المواعيد للمشاغل والصالونات</p>
    <p>هذه الرسالة أُرسلت إلى {{ $ownerEmail }}</p>
  </div>
</div>
</body>
</html>
