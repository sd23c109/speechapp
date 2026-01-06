(function() {
    const scriptTag = document.currentScript;
    const userUuid = new URL(scriptTag.src).searchParams.get('user_uuid');
    const apiKey = new URL(scriptTag.src).searchParams.get('key');
    const apiUrl = 'https://app.mkadvantage.com/api/testimonials-submit.php';
    const configUrl = 'https://app.mkadvantage.com/api/testimonials-get-form-config.php?user_uuid=' + encodeURIComponent(userUuid);

    const container = document.getElementById('mka-testimonial-form');
    if (!container) {
        console.error('MKA Testimonials Form: Missing container');
        return;
    }

    // Create basic form HTML
    container.innerHTML = `
        <div id="mka-testimonial-container" style="max-width:500px; margin:0 auto; text-align:left;">

    <div id="rewardBanner" style="text-align: center; display:none; border:1px solid #ccc; padding:10px; background:#f8f9fa; margin-bottom:10px; width:100%; box-sizing:border-box;">
        <h2><strong id="rewardTitle"></strong></h2>
        <p id="rewardDescription"></p>
        <p id="rewardExpire"></p>
        <small id="rewardFinePrint" style="color:#666;"></small>
    </div>

    <form id="testimonialForm" style="width:100%; box-sizing:border-box;">
        <input type="text" name="user_name" placeholder="Your Name" required style="width:100%; padding:10px; margin-bottom:10px; box-sizing:border-box;">
        <input type="text" name="user_email" placeholder="Your Email" required style="width:100%; padding:10px; margin-bottom:10px; box-sizing:border-box;">
        <textarea name="testimonial_text" placeholder="Your Testimonial" required style="width:100%; padding:10px; margin-bottom:10px; box-sizing:border-box;"></textarea>
        <input type="hidden" name="reward_id" id="reward_id" value="">
        <button type="submit" style="width:100%; padding:10px; font-size:14px; cursor:pointer; box-sizing:border-box;">Submit</button>
        <div id="testimonialMessage" style="margin-top:10px; text-align:center; color:green;"></div>
    </form>

</div>
    `;

    const form = document.getElementById('testimonialForm');
    const message = document.getElementById('testimonialMessage');

    // Fetch form config (reward info)
    fetch(configUrl)
        .then(res => res.json())
        .then(config => {
            if (config.reward_id && !config.is_expired) {
                document.getElementById('rewardBanner').style.display = 'block';

                // Format expires_at into "Offer Expires June 30, 2025"
                const expireDate = new Date(config.expires_at);
                const options = { year: 'numeric', month: 'long', day: 'numeric' };
                const formattedExpire = expireDate.toLocaleDateString(undefined, options);

                document.getElementById('rewardExpire').innerText = `Offer Expires ${formattedExpire}`;

                document.getElementById('rewardTitle').innerText = config.reward_title;
                document.getElementById('rewardDescription').innerText = config.reward_description;
                document.getElementById('rewardFinePrint').innerText = config.fine_print;
                document.getElementById('reward_id').value = config.reward_id;
            } else if (config.reward_id && config.is_expired) {
                console.warn('Reward exists but is expired; not showing banner.');
            } else {
                console.log('No reward configured.');
            }
        })
        .catch(err => {
            console.error('Error loading form config:', err);
        });

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const data = {
            user_name: form.user_name.value,
            user_email: form.user_email.value,
            testimonial_text: form.testimonial_text.value,
            reward_id: form.reward_id.value || null // pass reward_id if present
        };

        fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${apiKey}`
            },
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                message.innerHTML = 'Thank you! Your testimonial was submitted.';
                form.reset();
                document.getElementById('rewardBanner').style.display = 'none';
            } else {
                message.innerHTML = 'Error: ' + res.error;
            }
        })
        .catch(err => {
            console.error('Error submitting testimonial:', err);
            message.innerHTML = 'An unexpected error occurred.';
        });
    });
})();
