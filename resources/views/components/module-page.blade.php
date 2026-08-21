@props(['page'])

<div class="native-page-module" data-module="{{ $page['key'] }}">
    <template data-module-template>
        {!! $page['styles'] !!}
        <style>
            :host { display: block; }
            #nav-menu-popup,
            .c-section:has(.c-nav-menu),
            .footersection { display: none !important; }
        </style>
        {!! $page['markup'] !!}
    </template>
</div>

<script>
    (() => {
        const host = document.currentScript.previousElementSibling;
        const template = host?.querySelector('[data-module-template]');

        if (!host || !template || host.shadowRoot) return;

        const root = host.attachShadow({ mode: 'open' });
        root.append(template.content.cloneNode(true));

        @if ($page['key'] === 'join-us')
            const applicationUrl = @json(route('job-applications.create'));
            root.querySelectorAll('a').forEach((link) => {
                const label = (link.textContent || '').toLowerCase().replace(/[^a-z]/g, '');

                if (label === 'applynow') {
                    link.href = applicationUrl;
                    link.removeAttribute('target');
                    link.removeAttribute('rel');
                }
            });
        @endif

        @if ($page['key'] === 'faqs')
            const faqItems = [...root.querySelectorAll('.hl-faq-child')];

            faqItems.forEach((item, index) => {
                const heading = item.querySelector('.hl-faq-child-heading');
                const panel = item.querySelector('.hl-faq-child-panel');

                if (!heading || !panel) return;

                const panelId = `ainchors-faq-answer-${index + 1}`;
                panel.id = panelId;
                panel.hidden = true;
                heading.setAttribute('role', 'button');
                heading.setAttribute('tabindex', '0');
                heading.setAttribute('aria-controls', panelId);
                heading.setAttribute('aria-expanded', 'false');
                heading.setAttribute('data-faq-accordion', 'true');

                const setExpanded = (expanded) => {
                    item.classList.toggle('active', expanded);
                    panel.hidden = !expanded;
                    heading.setAttribute('aria-expanded', String(expanded));
                };

                const toggle = () => setExpanded(panel.hidden);

                heading.addEventListener('click', toggle);
                heading.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        toggle();
                    }
                });
            });

            const faqStyles = document.createElement('style');
            faqStyles.textContent = `
                .hl-faq {
                    width: min(100%, 1280px);
                    margin-inline: auto !important;
                }
                .hl-faq-child {
                    margin-bottom: 14px !important;
                }
                .hl-faq-child-heading[data-faq-accordion="true"] { cursor: pointer; }
                .hl-faq-child-heading[data-faq-accordion="true"] {
                    min-height: 76px;
                    padding: 18px 22px !important;
                    text-align: left;
                }
                .hl-faq-child-heading[data-faq-accordion="true"] .hl-faq-child-heading-icon {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    width: 24px;
                    min-width: 24px;
                    height: 24px;
                    margin-left: auto;
                }
                .hl-faq-child-heading[data-faq-accordion="true"] .hl-faq-child-heading-icon::before {
                    content: '' !important;
                    display: block;
                    width: 9px;
                    height: 9px;
                    border-right: 2px solid currentColor;
                    border-bottom: 2px solid currentColor;
                    font-family: inherit !important;
                    transform: rotate(45deg);
                    transition: transform .2s ease;
                }
                #faq--4WQKiUkFx .hl-faq-child-heading[data-faq-accordion="true"] .hl-faq-child-heading-icon::before {
                    content: '' !important;
                    font-family: inherit !important;
                }
                .hl-faq-child.active .hl-faq-child-heading[data-faq-accordion="true"] .hl-faq-child-heading-icon::before {
                    transform: rotate(225deg);
                }
                .hl-faq-child-panel[hidden] { display: none !important; }
                .hl-faq-child.active .hl-faq-child-panel:not([hidden]) {
                    height: auto !important;
                    opacity: 1 !important;
                    overflow: visible !important;
                    padding: 16px 22px 22px !important;
                    border-radius: 0 0 10px 10px !important;
                }
                .hl-faq-child.active .hl-faq-child-heading[data-faq-accordion="true"] {
                    border-radius: 10px 10px 0 0 !important;
                }
            `;
            root.append(faqStyles);
        @endif

        @if ($page['key'] === 'contact')
            const contactEmailUrl = 'https://mail.google.com/mail/?view=cm&fs=1&to=info@ainchors.com';
            root.querySelectorAll('a.__cf_email__, a[href*="/cdn-cgi/l/email-protection"]').forEach((link) => {
                link.href = contactEmailUrl;
                link.textContent = 'info@ainchors.com';
                link.setAttribute('target', '_blank');
                link.setAttribute('rel', 'noopener noreferrer');
            });

            const qrTrigger = root.querySelector('#button-P1eb2q_TSk_btn')
                ?? [...root.querySelectorAll('button')].find((button) =>
                    (button.textContent || '').trim().toLowerCase() === 'quick scan here!'
                );

            if (qrTrigger) {
                const qrModal = document.createElement('div');
                qrModal.className = 'ainchors-contact-qr-modal';
                qrModal.hidden = true;
                qrModal.setAttribute('role', 'dialog');
                qrModal.setAttribute('aria-modal', 'true');
                qrModal.setAttribute('aria-label', 'WhatsApp QR code');

                const qrPanel = document.createElement('div');
                qrPanel.className = 'ainchors-contact-qr-panel';

                const closeQr = document.createElement('button');
                closeQr.type = 'button';
                closeQr.className = 'ainchors-contact-qr-close';
                closeQr.setAttribute('aria-label', 'Close WhatsApp QR code');
                closeQr.textContent = '×';

                const qrImage = document.createElement('img');
                qrImage.src = @json(asset('assets/site/699e42092552e408a75e24ce.png'));
                qrImage.alt = 'WhatsApp AINCHORS QR code';

                qrPanel.append(closeQr, qrImage);
                qrModal.append(qrPanel);
                root.append(qrModal);

                const setQrOpen = (open) => {
                    qrModal.hidden = !open;
                    if (open) closeQr.focus();
                    else qrTrigger.focus();
                };

                qrTrigger.addEventListener('click', (event) => {
                    event.preventDefault();
                    setQrOpen(true);
                });
                closeQr.addEventListener('click', () => setQrOpen(false));
                qrModal.addEventListener('click', (event) => {
                    if (event.target === qrModal) setQrOpen(false);
                });
                root.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && !qrModal.hidden) setQrOpen(false);
                });

                const qrStyles = document.createElement('style');
                qrStyles.textContent = `
                    .ainchors-contact-qr-modal {
                        position: fixed;
                        inset: 0;
                        z-index: 1000;
                        display: grid;
                        place-items: center;
                        padding: 24px;
                        background: rgba(0, 0, 0, .5);
                    }
                    .ainchors-contact-qr-modal[hidden] { display: none !important; }
                    .ainchors-contact-qr-panel {
                        position: relative;
                        width: min(720px, calc(100vw - 32px));
                        padding: 20px;
                        border: 10px solid var(--gray);
                        background: var(--white);
                        text-align: center;
                    }
                    .ainchors-contact-qr-panel img {
                        display: block;
                        width: min(250px, 100%);
                        height: auto;
                        margin: 0 auto;
                    }
                    .ainchors-contact-qr-close {
                        position: absolute;
                        top: -14px;
                        right: -14px;
                        z-index: 1;
                        width: 32px;
                        height: 32px;
                        border: 0;
                        border-radius: 50%;
                        background: var(--white);
                        color: var(--black);
                        font-size: 26px;
                        line-height: 1;
                        cursor: pointer;
                    }
                `;
                root.append(qrStyles);
            }

            root.addEventListener('submit', async (event) => {
                const form = event.target;
                if (!(form instanceof HTMLFormElement)) return;

                event.preventDefault();

                const value = (selector) => form.querySelector(selector)?.value?.trim() ?? '';
                const payload = {
                    full_name: value('[name="full_name"]') || value('[name="last_name"]'),
                    email: value('[name="email"]'),
                    phone: value('[name="phone"]'),
                    country: value('[name="country"]'),
                    message: value('[data-q="comment"]'),
                    source: 'contact_page',
                };

                try {
                    const response = await fetch(@json(route('contact.submit')), {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': @json(csrf_token()),
                        },
                        body: JSON.stringify(payload),
                    });
                    const result = await response.json();
                    let notice = form.querySelector('.ainchors-contact-feedback');

                    if (!notice) {
                        notice = document.createElement('p');
                        notice.className = 'ainchors-contact-feedback';
                        form.append(notice);
                    }

                    notice.textContent = response.ok
                        ? result.message
                        : Object.values(result.errors ?? {}).flat().join(' ');
                    notice.style.color = response.ok ? '#37AD82' : '#b42318';
                } catch (_) {
                    const notice = document.createElement('p');
                    notice.className = 'ainchors-contact-feedback';
                    notice.textContent = 'Unable to submit right now. Please try again.';
                    notice.style.color = '#b42318';
                    form.append(notice);
                }
            });
        @endif
    })();
</script>
