<p>یک پیام تازه از فرم تماس کیوسک دریافت شد.</p>
<p><strong>نام:</strong> {{ $contact['name'] }}</p>
<p><strong>ایمیل:</strong> <a href="mailto:{{ $contact['email'] }}">{{ $contact['email'] }}</a></p>
<p><strong>موضوع:</strong> {{ $contact['subject'] ?? 'بدون موضوع' }}</p>
<p><strong>پیام:</strong><br>{!! nl2br(e($contact['message'])) !!}</p>
