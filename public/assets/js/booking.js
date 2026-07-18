document.addEventListener('DOMContentLoaded', () => {
    const service = document.querySelector('#service_id');
    const barber = document.querySelector('#barber_id');
    const date = document.querySelector('#appointment_date');
    const slots = document.querySelector('#slots');
    const submit = document.querySelector('#booking-submit');

    if (!service || !barber || !date || !slots) return;

    const loadSlots = async () => {
        slots.innerHTML = '<div class="loading-box">Buscando horarios disponibles…</div>';
        submit.disabled = true;

        if (!service.value || !date.value) {
            slots.innerHTML = '<div class="empty-box">Elegí un servicio y una fecha.</div>';
            return;
        }

        const params = new URLSearchParams({
            service_id: service.value,
            barber_id: barber.value || '0',
            date: date.value,
        });

        try {
            const response = await fetch(`/api/disponibilidad.php?${params.toString()}`);
            const data = await response.json();

            if (!response.ok) throw new Error(data.message || 'No se pudo consultar la agenda.');
            if (!data.slots.length) {
                slots.innerHTML = '<div class="empty-box">No quedan horarios para esa fecha. Probá con otro día.</div>';
                return;
            }

            slots.innerHTML = data.slots.map((slot, index) => `
                <div class="slot">
                    <input type="radio" id="slot-${index}" name="slot" value="${slot.time}|${slot.barber_id}" required>
                    <label for="slot-${index}">
                        ${slot.time}
                        ${barber.type !== 'hidden' && barber.value === '0' ? `<small>${escapeHtml(slot.barber_name)}</small>` : ''}
                    </label>
                </div>
            `).join('');

            slots.querySelectorAll('input[name="slot"]').forEach(input => {
                input.addEventListener('change', () => { submit.disabled = false; });
            });
        } catch (error) {
            slots.innerHTML = `<div class="empty-box">${escapeHtml(error.message)}</div>`;
        }
    };

    const escapeHtml = value => String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    service.addEventListener('change', loadSlots);
    barber.addEventListener('change', loadSlots);
    date.addEventListener('change', loadSlots);

    if (service.value && date.value) loadSlots();
});
