@props(['footer'])

<footer class="site-footer">
    <div class="site-shell footer-grid">
        <div class="footer-brand">
            <img src="{{ asset('assets/footer-logo.webp') }}" alt="AINCHORS Training & Consulting">
            <div class="footer-link-grid">
                <div><h2>Explore Site</h2><a href="{{ url('/about-us-814253') }}">About us</a><a href="{{ url('/courses') }}">Courses</a><a href="{{ url('/testimonials') }}">Testimonials</a><a href="{{ url('/faqs') }}">FAQ’s</a><a href="{{ url('/events') }}">Events</a></div>
                <div><h2>Useful Links</h2><a href="{{ url('/contact-us') }}">Contact us</a><a href="{{ url('/terms--conditions') }}">Terms &amp; Conditions</a><a href="{{ url('/privacy--policy') }}">Privacy Policy</a></div>
            </div>
        </div>
        <div class="footer-locations">
            <h2>Locations</h2>
            <h3>Australia:</h3>
            @foreach ($footer['australia'] as $line)<p>{{ $line }}</p>@endforeach
            <p>Email: <a href="mailto:info@ainchors.com">info@ainchors.com</a></p>
            <p>WhatsApp: <a href="https://wa.me/+61418802086">https://wa.me/+61418802086</a></p>
            <h3>Malaysia:</h3>
            @foreach ($footer['malaysia'] as $line)<p>{{ $line }}</p>@endforeach
            <div class="social-links">
                @foreach (['facebook' => 'Facebook', 'instagram' => 'Instagram', 'linkedin' => 'LinkedIn', 'tiktok' => 'TikTok', 'whatsapp' => 'WhatsApp', 'mail' => 'Email'] as $icon => $label)
                    <a href="{{ $icon === 'mail' ? 'mailto:info@ainchors.com' : ($icon === 'whatsapp' ? 'https://wa.me/+61418802086' : '#') }}" aria-label="{{ $label }}"><img src="{{ asset('assets/'.$icon.'.svg') }}" alt=""></a>
                @endforeach
            </div>
        </div>
        <div class="footer-contact">
            <h2>Begin Your Journey Today!</h2><p>Contact Us!</p>
            <form @submit.prevent="$refs.status.textContent = 'Thank you.'" class="contact-form">
                <label><span>Full Name</span><input type="text" placeholder="Full Name" required></label>
                <label><span>Email</span><input type="email" placeholder="Email*" required></label>
                <label><span>Phone</span><input type="tel" placeholder="Phone*" required></label>
                <label><span>Country</span><select required><option value="">Country</option><option>Australia</option><option>Malaysia</option><option>Other</option></select></label>
                <button type="submit">Submit</button><p x-ref="status" role="status"></p>
            </form>
        </div>
    </div>
    <p class="copyright">Copyright 2025. All Right are Reserved. AINCHORS Training &amp; Consulting</p>
</footer>
