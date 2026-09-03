<style>
.payment-state-card { width: min(100%, 620px); min-height: 700px; justify-content: flex-start; padding-top: 64px; padding-bottom: 52px; }
.payment-state-actions { margin-top: auto; }
.payment-state-button:hover,.payment-state-button:focus-visible { background: #e8fff7; color: #37ad82; border-color: #37ad82; box-shadow: 0 8px 18px rgba(55,173,130,.18); transform: translateY(-1px); }
.payment-result-title { font-size: clamp(22px,7vw,40px); max-width: 100%; white-space: nowrap; line-height: 1.13; }
@media (max-width: 640px) {
    .payment-state-card { min-height: auto; padding-top: 40px; padding-bottom: 36px; }
    .payment-state-actions { margin-top: 28px; }
    .payment-result-title { font-size: clamp(22px,7vw,30px); }
}
</style>
