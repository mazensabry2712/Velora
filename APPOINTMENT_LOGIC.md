# منطق تنسيق المواعيد والطابور

## نظرة عامة
تم إعادة هيكلة منطق المواعيد والطابور لضمان التنسيق الكامل بين جميع أجزاء النظام. كل تغيير في حالة الموعد أو الطابور يؤثر تلقائيًا على الآخر.

---

## 🔄 التنسيق التلقائي (Model Events)

### 1. عند تحديث حالة الموعد (Appointment Model)
```php
Appointment::updating() {
    // تحديث الطابور تلقائياً عند تغيير حالة الموعد
    
    if (status = 'cancelled') → queue->status = 'cancelled'
    if (status = 'completed') → queue->status = 'completed'  
    if (status = 'confirmed' && queue was cancelled) → queue->status = 'waiting'
}
```

### 2. عند حذف الموعد (Appointment Model)
```php
Appointment::deleting() {
    // حذف الطابور تلقائياً عند حذف الموعد
    appointment->queue->delete()
}
```

### 3. عند تحديث حالة الطابور (Queue Model)
```php
Queue::updating() {
    // تحديث الموعد تلقائياً عند تغيير حالة الطابور
    
    if (status = 'completed') → appointment->status = 'completed'
    if (status = 'cancelled' or 'skipped') → appointment->status = 'cancelled'
    if (status = 'serving' && appointment not confirmed) → appointment->status = 'confirmed'
}
```

---

## ✅ قواعد العمل (Business Rules)

### إضافة موعد للطابور (`addAppointmentToQueue`)
**الشروط المطلوبة:**
- ✅ الموعد غير موجود في الطابور بالفعل
- ✅ حالة الموعد ليست `cancelled` أو `completed`
- ✅ تاريخ الموعد اليوم أو في المستقبل (ليس في الماضي)

**الإجراءات التلقائية:**
1. إنشاء رقم طابور تسلسلي
2. نقل الحالة VIP من العميل إلى الطابور
3. تأكيد الموعد تلقائياً (`pending` → `confirmed`)

```php
// مثال
$appointment->canBeAddedToQueue() // يتحقق من كل الشروط
```

### إزالة موعد من الطابور (`removeFromQueue`)
**الإجراءات التلقائية:**
- إذا كان الطابور `completed` → الموعد يصبح `completed`
- إذا كان الطابور `cancelled/skipped` → الموعد يصبح `cancelled`
- إذا كان الطابور `waiting/serving` → الموعد يبقى `confirmed`

### تعديل موعد (`updateAppointment`)
**التنسيق التلقائي:**
- إذا تغير التاريخ أو الوقت:
  - إذا الموعد أصبح في الماضي → حذف الطابور تلقائياً
  - تحديث معلومات الطابور

---

## 🔍 Helper Methods جديدة في Appointment Model

### 1. `canBeAddedToQueue(): bool`
يتحقق إذا كان الموعد يستوفي شروط الإضافة للطابور
```php
$appointment->canBeAddedToQueue() 
// false إذا: موجود بالطابور، ملغي، مكتمل
```

### 2. `isOverdue(): bool`
يتحقق إذا كان الموعد متأخر (في الماضي وليس مكتمل)
```php
$appointment->isOverdue()
// true إذا: date < today && status != 'completed'
```

### 3. `isSoon(): bool`
يتحقق إذا كان الموعد قريب (خلال ساعتين)
```php
$appointment->isSoon()
// true إذا: اليوم && خلال ساعتين
```

### 4. `getServiceNameAttribute`
يعيد اسم الخدمة (عربي أو إنجليزي حسب اللغة)

---

## 📊 Eloquent Scopes جديدة

```php
// مواعيد اليوم
Appointment::today()->get()

// مواعيد قادمة (مستقبلية وليست ملغية أو مكتملة)
Appointment::upcoming()->get()

// مواعيد معلقة
Appointment::pending()->get()

// مواعيد مؤكدة
Appointment::confirmed()->get()

// مواعيد موجودة في الطابور (waiting أو serving)
Appointment::inQueue()->get()
```

---

## 🔗 سيناريوهات التنسيق الشائعة

### السيناريو 1: إضافة موعد جديد مع الطابور
```javascript
// في النموذج
<input type="checkbox" name="add_to_queue"> إضافة للطابور تلقائياً

// Controller يتعامل معها
if ($request->add_to_queue) {
    Queue::create([...]) // ينشئ الطابور
    $appointment->update(['status' => 'confirmed']) // يؤكد الموعد
}
```

### السيناريو 2: إلغاء موعد موجود في الطابور
```php
$appointment->update(['status' => 'cancelled'])
// تلقائياً: queue->status = 'cancelled' عبر Model Event
```

### السيناريو 3: استكمال خدمة العميل في الطابور
```php
$queue->update(['status' => 'completed'])
// تلقائياً: appointment->status = 'completed' عبر Model Event
```

### السيناريو 4: تعديل تاريخ موعد له طابور
```php
$appointment->update(['date' => '2024-01-10'])
// Controller يتحقق:
if (new_date < today && has_queue) {
    queue->delete() // يحذف الطابور
}
```

---

## 🎯 نقاط القوة في المنطق الجديد

### ✅ التنسيق الثنائي الاتجاه
- تغيير الموعد يحدث الطابور
- تغيير الطابور يحدث الموعد
- لا يوجد حالات عزل أو تضارب

### ✅ التحقق التلقائي
- لا يمكن إضافة مواعيد ماضية للطابور
- لا يمكن إضافة مواعيد ملغية/مكتملة
- حذف الطابور عند نقل الموعد للماضي

### ✅ الوضوح والصيانة
- كل القواعد في مكان واحد (Model Events)
- Helper Methods توضح المنطق
- Scopes تسهل الاستعلامات
- توثيق كامل للقواعد

### ✅ منع الأخطاء
- حذف الموعد يحذف الطابور تلقائياً (Cascade)
- لا توجد queue orphaned
- تزامن كامل بين الحالات

---

## 📝 ملاحظات للمطورين

### عند إضافة ميزات جديدة:
1. استخدم Scopes الموجودة بدلاً من WHERE يدوي
2. استخدم Helper Methods للتحقق من الحالات
3. Model Events تتعامل مع التنسيق تلقائياً - لا تعيد تنفيذها

### عند debugging:
```php
// تحقق من قواعد الموعد
$appointment->canBeAddedToQueue()
$appointment->isOverdue()
$appointment->isSoon()

// استعلامات سريعة
Appointment::today()->inQueue()->count()
Appointment::upcoming()->whereDoesntHave('queue')->count()
```

---

## 🚀 أمثلة استخدام في الواجهة

### عرض مواعيد اليوم في الطابور
```php
$appointments = Appointment::today()
    ->inQueue()
    ->with(['customer', 'queue'])
    ->get();
```

### عرض مواعيد قادمة بدون طابور
```php
$noQueue = Appointment::upcoming()
    ->whereDoesntHave('queue')
    ->get();
```

### إحصائيات سريعة
```php
$stats = [
    'overdue' => Appointment::where('date', '<', today())
        ->where('status', '!=', 'completed')
        ->count(),
    'today_in_queue' => Appointment::today()->inQueue()->count(),
    'upcoming_confirmed' => Appointment::upcoming()->confirmed()->count(),
];
```

---

## التحديثات المستقبلية المقترحة

### 1. التحقق من تضارب المواعيد
```php
// منع حجز نفس الموظف في نفس الوقت
public function hasConflict(): bool
```

### 2. التحقق من توفر الموظفين
```php
// التحقق من جدول الموظف
public function isStaffAvailable(): bool
```

### 3. قواعد حسب نوع الخدمة
```php
// بعض الخدمات لا تحتاج طابور
public function serviceRequiresQueue(): bool
```

---

تم التوثيق: 2024
آخر تحديث: نظام التنسيق الكامل بين المواعيد والطابور
