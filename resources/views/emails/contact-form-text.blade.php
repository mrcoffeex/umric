New message from the website contact form
============================================

Name:   {{ $fromName }}
Email:  {{ $visitorEmail }}
Role:   {{ $role ? ucfirst($role) : '—' }}
IP:     {{ $submitterIp }}

---------------------------------------------

{{ $bodyText }}
