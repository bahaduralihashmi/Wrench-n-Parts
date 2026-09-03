/**
 * Shared Image Upload System for Registration Forms
 * - Single upload fields (CNIC, Certificate): 1 image, hide "+" after upload, remove to re-enable
 * - Multi upload fields (Shop/Workshop pictures): unlimited, always show "+", gallery with delete
 * - Client-side validation: JPG/JPEG/PNG/WEBP only, 5MB max
 * - Smooth animations, progress indicator, responsive
 */

const UploadSystem = (() => {
    const ALLOWED_TYPES = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    const MAX_SIZE = 5 * 1024 * 1024; // 5MB

    function init(config) {
        config.singles.forEach(field => setupSingle(field));
        config.multi && setupMulti(config.multi);
    }

    function setupSingle(field) {
        const wrapper = document.getElementById(field.wrapperId);
        const input = document.getElementById(field.inputId);
        const uploadArea = wrapper.querySelector('.upload-area');
        const uploadBtn = wrapper.querySelector('.upload-add-btn');
        const previewWrap = wrapper.querySelector('.upload-preview-wrap');
        const previewImg = wrapper.querySelector('.upload-preview-img');
        const removeBtn = wrapper.querySelector('.upload-remove-btn');
        const errorEl = wrapper.querySelector('.upload-error');

        uploadArea.addEventListener('click', (e) => {
            if (e.target.closest('.upload-remove-btn')) return;
            input.click();
        });

        input.addEventListener('change', () => {
            const file = input.files[0];
            if (!file) return;

            const error = validateFile(file);
            if (error) {
                showError(errorEl, error);
                input.value = '';
                return;
            }
            hideError(errorEl);

            const reader = new FileReader();
            reader.onload = (e) => {
                previewImg.src = e.target.result;
                previewWrap.classList.add('has-image');
                uploadArea.style.display = 'none';
                previewWrap.style.animation = 'uploadFadeIn 0.3s ease';
            };
            reader.readAsDataURL(file);
        });

        removeBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            input.value = '';
            previewImg.src = '';
            previewWrap.classList.remove('has-image');
            uploadArea.style.display = '';
            previewWrap.style.animation = 'uploadFadeOut 0.25s ease';
            setTimeout(() => { previewWrap.style.animation = ''; }, 250);
        });
    }

    function setupMulti(config) {
        const wrapper = document.getElementById(config.wrapperId);
        const input = document.getElementById(config.inputId);
        const uploadArea = wrapper.querySelector('.upload-area');
        const gallery = wrapper.querySelector('.upload-gallery');
        const errorEl = wrapper.querySelector('.upload-error');
        let files = [];

        uploadArea.addEventListener('click', () => input.click());

        function syncInput() {
            const dt = new DataTransfer();
            files.forEach(f => dt.items.add(f.file));
            input.files = dt.files;
        }

        input.addEventListener('change', () => {
            const newFiles = Array.from(input.files);
            const validFiles = [];

            newFiles.forEach(file => {
                const error = validateFile(file);
                if (error) {
                    showError(errorEl, error);
                } else {
                    validFiles.push(file);
                }
            });

            if (validFiles.length === 0) {
                input.value = '';
                return;
            }
            hideError(errorEl);

            validFiles.forEach(file => {
                const id = 'file_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5);
                const reader = new FileReader();
                reader.onload = (e) => {
                    const item = createGalleryItem(id, e.target.result, file.name);
                    gallery.appendChild(item);
                    item.style.animation = 'uploadFadeIn 0.3s ease';
                };
                reader.readAsDataURL(file);
                files.push({ id, file });
            });

            syncInput();
        });

        gallery.addEventListener('click', (e) => {
            const removeBtn = e.target.closest('.gallery-remove-btn');
            if (!removeBtn) return;
            const item = removeBtn.closest('.gallery-item');
            const id = item.dataset.id;
            files = files.filter(f => f.id !== id);
            item.style.animation = 'uploadFadeOut 0.25s ease';
            setTimeout(() => item.remove(), 250);
            syncInput();
        });
    }

    function createGalleryItem(id, src, name) {
        const div = document.createElement('div');
        div.className = 'gallery-item';
        div.dataset.id = id;
        div.innerHTML = `
            <img src="${src}" alt="${name}">
            <button type="button" class="gallery-remove-btn" title="Remove">
                <i class="fas fa-times"></i>
            </button>
            <div class="gallery-item-name" title="${name}">${truncate(name, 18)}</div>
        `;
        return div;
    }

    function validateFile(file) {
        if (!ALLOWED_TYPES.includes(file.type)) {
            return 'Only JPG, JPEG, PNG, and WEBP images are allowed.';
        }
        if (file.size > MAX_SIZE) {
            return 'File must be under 5MB.';
        }
        return null;
    }

    function showError(el, msg) {
        el.textContent = msg;
        el.style.display = 'block';
        el.style.animation = 'uploadFadeIn 0.2s ease';
    }

    function hideError(el) {
        el.textContent = '';
        el.style.display = 'none';
    }

    function truncate(str, len) {
        return str.length > len ? str.substring(0, len) + '...' : str;
    }

    return { init };
})();
