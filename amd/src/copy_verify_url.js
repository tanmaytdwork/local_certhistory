import {add as addToast} from 'core/toast';
import {get_string as getString} from 'core/str';

const init = () => {
    document.addEventListener('click', async(e) => {
        const btn = e.target.closest('[data-action="copy-verify-url"]');
        if (!btn) {
            return;
        }
        e.preventDefault();

        const url = btn.dataset.url;

        try {
            await navigator.clipboard.writeText(url);
        } catch (err) {
            // Fallback for browsers without clipboard API support.
            const textarea = document.createElement('textarea');
            textarea.value = url;
            textarea.style.cssText = 'position:fixed;opacity:0';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
        }

        const msg = await getString('linkcopied', 'local_certhistory');
        addToast(msg, {type: 'success'});
    });
};

export {init};
