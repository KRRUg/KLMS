import 'cropperjs';
import '../../css/site/profile.scss';

const MAX_IMAGE_SIZE = { width: 1600, height: 1200 };
const CROP_OUTPUT_SIZE = 200;

// Scale down large images before cropping
const scaleImage = (src, maxWidth, maxHeight) => new Promise((resolve) => {
    if (!src) return resolve(src);

    const img = new Image();
    img.onload = () => {
        const scale = Math.min(maxWidth / img.width, maxHeight / img.height, 1);
        if (scale >= 1) return resolve(src);

        const canvas = Object.assign(document.createElement('canvas'), {
            width: Math.round(img.width * scale),
            height: Math.round(img.height * scale)
        });
        
        const ctx = canvas.getContext('2d');
        if (!ctx) return resolve(src);

        ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
        
        const mimeType = src.match(/^data:(.*?);/)?.[1] || 'image/jpeg';
        resolve(canvas.toDataURL(mimeType, mimeType === 'image/jpeg' ? 0.92 : undefined));
    };
    img.onerror = () => resolve(src);
    img.src = src;
});

const initProfileImageEditor = () => {
    const container = document.querySelector('[data-profile-image-field]');
    if (!container) return;

    // DOM elements - search for VichUploader generated fields
    const formFieldsContainer = document.getElementById('profile-image-form-fields');
    const elements = {
        fileInput: formFieldsContainer?.querySelector('input[type="file"]'),
        selectButton: container.querySelector('.js-profile-image-select'),
        removeButton: container.querySelector('.js-profile-image-remove'),
        deleteField: formFieldsContainer?.querySelector('input[type="checkbox"]'),
        preview: container.querySelector('#profile-image-preview'),
        placeholder: container.querySelector('#profile-image-placeholder'),
        modal: document.getElementById('profileImageCropModal'),
        cropSave: document.getElementById('profileImageCropSave')
    };

    const cropper = {
        canvas: elements.modal?.querySelector('cropper-canvas'),
        get image() { return this.canvas?.querySelector('cropper-image'); },
        get selection() { return this.canvas?.querySelector('cropper-selection'); }
    };

    if (!elements.fileInput || !elements.preview) return;

    // State
    let objectUrl = null;
    let pendingImage = null;
    let isCropping = false;

    // Helpers
    const setDelete = (state) => {
        if (!elements.deleteField) return;
        const prop = elements.deleteField.type === 'checkbox' ? 'checked' : 'value';
        elements.deleteField[prop] = state ? (prop === 'checked' ? true : '1') : (prop === 'checked' ? false : '');
    };

    const updatePreview = (src, markAsInitial = false) => {
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = src?.startsWith('blob:') ? src : null;

        Object.assign(elements.preview, { src: src || '' });
        elements.preview.style.display = src ? 'block' : 'none';
        if (elements.placeholder) elements.placeholder.style.display = src ? 'none' : '';
        if (elements.removeButton) elements.removeButton.style.display = src ? '' : 'none';

        if (markAsInitial) {
            Object.assign(elements.preview.dataset, {
                initialSrc: src || '',
                hasImage: src ? '1' : '0'
            });
        }
    };

    // Initialize cropper selection
    const initCropper = () => {
        if (!pendingImage || !cropper.image || !cropper.selection || !cropper.canvas) return;

        cropper.image.src = pendingImage;
        
        const centerSelection = () => {
            const rect = cropper.canvas.getBoundingClientRect();
            if (!rect.width || !rect.height) {
                requestAnimationFrame(centerSelection);
                return;
            }

            const size = Math.min(rect.width, rect.height) * 0.8;
            Object.assign(cropper.selection, {
                x: (rect.width - size) / 2,
                y: (rect.height - size) / 2,
                width: size,
                height: size,
                aspectRatio: 1
            });
        };

        requestAnimationFrame(() => requestAnimationFrame(centerSelection));
    };

    const resetCropper = () => {
        cropper.image?.removeAttribute('src');
        if (cropper.selection) Object.assign(cropper.selection, { x: 0, y: 0, width: 0, height: 0 });
        if (isCropping) {
            elements.fileInput.value = '';
            updatePreview(elements.preview.dataset.initialSrc || '');
        }
        [isCropping, pendingImage] = [false, null];
    };

    // Initialize with existing image
    const initialSrc = elements.preview.dataset.initialSrc || '';
    if (elements.preview.dataset.hasImage === '1' && initialSrc) {
        updatePreview(initialSrc);
    }

    // Event: Modal shown/hidden
    window.jQuery(elements.modal)
        .on('shown.bs.modal', initCropper)
        .on('hidden.bs.modal', resetCropper);

    // Event: Select image button
    elements.selectButton?.addEventListener('click', () => elements.fileInput.click());

    // Event: Remove image button
    elements.removeButton?.addEventListener('click', () => {
        elements.fileInput.value = '';
        setDelete(true);
        updatePreview('', true);
    });

    // Event: Save cropped image
    elements.cropSave?.addEventListener('click', async () => {
        if (!cropper.selection) return;

        try {
            const canvas = await cropper.selection.$toCanvas({
                width: CROP_OUTPUT_SIZE,
                height: CROP_OUTPUT_SIZE,
                imageSmoothingQuality: 'high',
            });

            canvas?.toBlob((blob) => {
                if (!blob) return;

                const file = new File([blob], elements.fileInput.files[0]?.name || 'profilbild.jpg', { 
                    type: blob.type, 
                    lastModified: Date.now() 
                });
                
                const dt = new DataTransfer();
                dt.items.add(file);
                elements.fileInput.files = dt.files;

                setDelete(false);
                updatePreview(URL.createObjectURL(file), true);
                [isCropping] = [false];
                window.jQuery(elements.modal).modal('hide');
            }, 'image/jpeg');
        } catch (error) {
            console.error('Cropping failed:', error);
        }
    });

    // Event: File selected
    elements.fileInput.addEventListener('change', async (e) => {
        const file = e.target.files[0];
        if (!file) return;

        setDelete(false);

        const reader = new FileReader();
        reader.onload = async (ev) => {
            const dataUrl = ev.target?.result;
            if (!dataUrl) return;

            pendingImage = await scaleImage(dataUrl, MAX_IMAGE_SIZE.width, MAX_IMAGE_SIZE.height);
            [isCropping] = [true];
            window.jQuery(elements.modal).modal({ backdrop: 'static', keyboard: false });
        };
        reader.readAsDataURL(file);
    });
};

// Initialize when DOM is ready
document.readyState === 'loading' 
    ? document.addEventListener('DOMContentLoaded', initProfileImageEditor)
    : initProfileImageEditor();

export default initProfileImageEditor;
