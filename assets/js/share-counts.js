(function () {
	'use strict';

	function copyToClipboard(text, onSuccess) {
		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(text).then(onSuccess, function () {});
			return;
		}
		var textarea = document.createElement('textarea');
		textarea.value = text;
		textarea.style.position = 'fixed';
		textarea.style.opacity = '0';
		document.body.appendChild(textarea);
		textarea.select();
		try {
			if (document.execCommand('copy')) {
				onSuccess();
			}
		} catch (e) { /* clipboard unavailable */ }
		document.body.removeChild(textarea);
	}

	document.addEventListener('click', function (event) {
		var link = event.target.closest('.toptal-social-share-wrapper a');
		if (!link || typeof window.toptalShareCount === 'undefined') {
			return;
		}

		var button = link.closest('div[class]');
		var wrapper = link.closest('.toptal-social-share-wrapper');
		var network = button ? button.className.split(' ')[0] : '';
		var postId = wrapper ? wrapper.getAttribute('data-post-id') : '';
		if (!network || !postId) {
			return;
		}

		if (link.getAttribute('data-toptal-action') === 'copy') {
			event.preventDefault();
			copyToClipboard(link.href, function () {
				button.classList.add('copied');
				setTimeout(function () { button.classList.remove('copied'); }, 1500);
			});
		}

		var body = new URLSearchParams({
			action: 'toptal_update_share_count',
			network: network,
			post_id: postId,
			nonce: toptalShareCount.nonce
		});

		fetch(toptalShareCount.ajax_url, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString()
		})
			.then(function (response) { return response.json(); })
			.then(function (result) {
				if (result && result.success) {
					var count = button.querySelector('.share-count');
					if (count) {
						count.textContent = result.data;
					}
				}
			})
			.catch(function () { /* counting is best-effort */ });
	});
})();
