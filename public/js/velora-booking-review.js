(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('bookingForm');
        if (!form) return;

        let confirmedSubmit = false;

        const style = document.createElement('style');
        style.textContent = `
            .vb-review-backdrop{position:fixed;inset:0;z-index:1000;display:grid;place-items:center;padding:20px;background:rgba(8,11,24,.58);backdrop-filter:blur(8px)}
            .vb-review-dialog{width:min(100%,520px);max-height:min(90vh,720px);overflow:auto;border:1px solid var(--velora-line,#E5E7EB);border-radius:24px;background:var(--velora-surface,#fff);color:var(--velora-text,#0D1226);box-shadow:0 30px 100px rgba(0,0,0,.24)}
            .vb-review-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding:22px 22px 12px}
            .vb-review-head h3{margin:0;font-size:20px;letter-spacing:-.02em}
            .vb-review-head p{margin:5px 0 0;color:var(--velora-text-muted,#687084);font-size:11px}
            .vb-review-close{width:34px;height:34px;border:1px solid var(--velora-line,#E5E7EB);border-radius:10px;background:transparent;color:inherit;cursor:pointer;font:inherit;font-size:18px}
            .vb-review-body{padding:10px 22px 6px}
            .vb-review-row{display:grid;grid-template-columns:110px 1fr;gap:14px;padding:13px 0;border-bottom:1px solid var(--velora-line,#E5E7EB)}
            .vb-review-row:last-child{border-bottom:0}
            .vb-review-row span{font-size:10px;font-weight:800;color:var(--velora-text-muted,#687084);text-transform:uppercase;letter-spacing:.05em}
            .vb-review-row strong{font-size:13px;overflow-wrap:anywhere}
            .vb-review-actions{display:flex;gap:10px;padding:16px 22px 22px}
            .vb-review-actions button{flex:1;min-height:52px;border-radius:14px;font:inherit;font-weight:800;cursor:pointer}
            .vb-review-cancel{border:1px solid var(--velora-line,#E5E7EB);background:var(--velora-surface-muted,#F5F7FA);color:inherit}
            .vb-review-confirm{border:0;background:var(--velora-gradient,linear-gradient(135deg,#006CFF,#6D46FF));color:#fff;box-shadow:0 12px 30px rgba(0,108,255,.20)}
            html.dark .vb-review-dialog{background:#0D1226;border-color:#252E45;color:#F8FAFC}
            html.dark .vb-review-close,html.dark .vb-review-cancel{background:#151C32;border-color:#252E45;color:#F8FAFC}
            @media(max-width:520px){.vb-review-backdrop{padding:12px}.vb-review-dialog{border-radius:20px}.vb-review-row{grid-template-columns:1fr;gap:4px}.vb-review-actions{flex-direction:column}}
        `;
        document.head.appendChild(style);

        function textOf(select) {
            return select?.selectedOptions?.[0]?.textContent?.trim() || '—';
        }

        function valueFor(id) {
            return document.getElementById(id)?.value?.trim() || '—';
        }

        function openReview() {
            if (document.querySelector('.vb-review-backdrop')) return;

            const service = textOf(document.getElementById('service_id'));
            const staff = textOf(document.getElementById('staff_id'));
            const date = valueFor('appointment_date');
            const time = textOf(document.getElementById('appointment_time'));
            const name = valueFor('name');
            const phone = valueFor('phone');
            const email = valueFor('email');

            const backdrop = document.createElement('div');
            backdrop.className = 'vb-review-backdrop';
            backdrop.setAttribute('role', 'presentation');
            backdrop.innerHTML = `
                <section class="vb-review-dialog" role="dialog" aria-modal="true" aria-labelledby="vb-review-title">
                    <div class="vb-review-head">
                        <div><h3 id="vb-review-title">Review your appointment</h3><p>Everything looks good? Confirm to book it.</p></div>
                        <button type="button" class="vb-review-close" aria-label="Close">×</button>
                    </div>
                    <div class="vb-review-body">
                        <div class="vb-review-row"><span>Service</span><strong></strong></div>
                        <div class="vb-review-row"><span>Specialist</span><strong></strong></div>
                        <div class="vb-review-row"><span>Date</span><strong></strong></div>
                        <div class="vb-review-row"><span>Time</span><strong></strong></div>
                        <div class="vb-review-row"><span>Name</span><strong></strong></div>
                        <div class="vb-review-row"><span>Phone</span><strong></strong></div>
                        <div class="vb-review-row"><span>Email</span><strong></strong></div>
                    </div>
                    <div class="vb-review-actions">
                        <button type="button" class="vb-review-cancel">Back & edit</button>
                        <button type="button" class="vb-review-confirm">Confirm appointment</button>
                    </div>
                </section>
            `;

            const values = [service, staff, date, time, name, phone, email];
            backdrop.querySelectorAll('.vb-review-row strong').forEach((node, index) => { node.textContent = values[index]; });
            document.body.appendChild(backdrop);

            const close = () => backdrop.remove();
            backdrop.querySelector('.vb-review-close').addEventListener('click', close);
            backdrop.querySelector('.vb-review-cancel').addEventListener('click', close);
            backdrop.addEventListener('click', (event) => { if (event.target === backdrop) close(); });

            const confirm = backdrop.querySelector('.vb-review-confirm');
            confirm.addEventListener('click', () => {
                confirmedSubmit = true;
                close();
                form.requestSubmit();
                confirmedSubmit = false;
            });
            confirm.focus();
        }

        form.addEventListener('submit', function (event) {
            if (confirmedSubmit) return;
            event.preventDefault();
            event.stopImmediatePropagation();
            if (!form.reportValidity()) return;
            openReview();
        }, true);
    });
})();
