(function() {
    const scriptTag = document.currentScript;
    const apiKey = new URL(scriptTag.src).searchParams.get('key');
   
    const apiUrl = 'https://app.mkadvantage.com/api/testimonials-approved.php';

    const container = document.getElementById('mka-testimonials');
    if (!container) {
        console.error('MKA Testimonials: Missing container #mka-testimonials');
        return;
    }

    fetch(apiUrl, {
        method: 'GET',
        headers: {
            'Authorization': `Bearer ${apiKey}`
        }
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) {
            container.innerHTML = '<p>Testimonials unavailable.</p>';
            return;
        }
        if (data.testimonials.length === 0) {
            container.innerHTML = '<p>No testimonials yet!</p>';
            return;
        }

        let html = '<div class="mka-testimonials">';
        data.testimonials.forEach(t => {
            html += `<blockquote>"${t.testimonial_text}"</blockquote>`;
            html += `<p>- ${t.user_name}</p>`;
        });
        html += '</div>';

        container.innerHTML = html;
    })
    .catch(err => {
        console.error('MKA Testimonials error:', err);
        container.innerHTML = '<p>Testimonials unavailable.</p>';
    });
})();
