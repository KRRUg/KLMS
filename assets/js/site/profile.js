import 'cropperjs';
import '../../css/site/profile.scss';

const MAX_IMAGE_SIZE = { width: 1600, height: 1200 };
const CROP_OUTPUT_SIZE = 200;
const ALLOWED_IMAGE_MIME_TYPES = ['image/png', 'image/jpeg', 'image/webp'];
const ALLOWED_IMAGE_EXTENSIONS = ['png', 'jpg', 'jpeg', 'webp'];
const IMAGE_UPLOAD_ERRORS = {
    invalidType: 'Ungültiger Dateityp. Erlaubt sind nur PNG, JPEG und WebP.',
    invalidContent: 'Die Datei ist kein gültiges Bild. Bitte PNG, JPEG oder WebP verwenden.'
};

const getFileExtension = (fileName) => {
    if (!fileName || !fileName.includes('.')) {
        return '';
    }

    return fileName.split('.').pop().toLowerCase();
};

const hasAllowedTypeAndExtension = (file) => {
    if (!file) {
        return false;
    }

    const extension = getFileExtension(file.name);

    return ALLOWED_IMAGE_MIME_TYPES.includes(file.type)
        && ALLOWED_IMAGE_EXTENSIONS.includes(extension);
};

const canRenderAsImage = async (file) => {
    if (!file) return false;

    if (typeof window.createImageBitmap === 'function') {
        try {
            const bitmap = await window.createImageBitmap(file);
            bitmap.close?.();
            return true;
        } catch {
            return false;
        }
    }

    return new Promise((resolve) => {
        const probe = new Image();
        const url = URL.createObjectURL(file);

        probe.onload = () => {
            URL.revokeObjectURL(url);
            resolve(true);
        };

        probe.onerror = () => {
            URL.revokeObjectURL(url);
            resolve(false);
        };

        probe.src = url;
    });
};

const validateSelectedImageFile = async (file) => {
    if (!hasAllowedTypeAndExtension(file)) {
        return IMAGE_UPLOAD_ERRORS.invalidType;
    }

    const isRealImage = await canRenderAsImage(file);
    if (!isRealImage) {
        return IMAGE_UPLOAD_ERRORS.invalidContent;
    }

    return null;
};

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
        error: container.querySelector('.js-profile-image-error'),
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

    const setClientError = (message = '') => {
        if (!elements.error) return;
        elements.error.textContent = message;
        elements.error.style.display = message ? '' : 'none';
    };

    const hideCropModal = () => {
        window.jQuery(elements.modal).modal('hide');
    };

    const showCropModal = () => {
        window.jQuery(elements.modal).modal({ backdrop: 'static', keyboard: false });
    };

    const setInputFile = (file) => {
        const dt = new DataTransfer();
        dt.items.add(file);
        elements.fileInput.files = dt.files;
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

    const openCropperForDataUrl = async (dataUrl) => {
        pendingImage = await scaleImage(dataUrl, MAX_IMAGE_SIZE.width, MAX_IMAGE_SIZE.height);
        isCropping = true;
        showCropModal();
    };

    const createCroppedImageFile = (blob) => {
        if (!blob) {
            return null;
        }

        return new File([blob], elements.fileInput.files[0]?.name || 'profilbild.jpg', {
            type: blob.type,
            lastModified: Date.now()
        });
    };

    const applyCroppedFile = (file) => {
        setInputFile(file);
        setClientError('');
        setDelete(false);
        updatePreview(URL.createObjectURL(file), true);
        isCropping = false;
        hideCropModal();
    };

    const handleSelectedFile = async (file) => {
        const validationError = await validateSelectedImageFile(file);
        if (validationError) {
            setClientError(validationError);
            elements.fileInput.value = '';
            return;
        }

        setClientError('');
        setDelete(false);

        const reader = new FileReader();
        reader.onload = async (ev) => {
            const dataUrl = ev.target?.result;
            if (!dataUrl) {
                return;
            }

            await openCropperForDataUrl(dataUrl);
        };
        reader.readAsDataURL(file);
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
        setClientError('');
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
                const file = createCroppedImageFile(blob);
                if (!file) {
                    return;
                }

                applyCroppedFile(file);
            }, 'image/jpeg');
        } catch (error) {
            console.error('Cropping failed:', error);
        }
    });

    // Event: File selected
    elements.fileInput.addEventListener('change', async (e) => {
        const file = e.target.files[0];
        if (!file) return;

        await handleSelectedFile(file);
    });
};

// Initialize when DOM is ready
document.readyState === 'loading' 
    ? document.addEventListener('DOMContentLoaded', initProfileImageEditor)
    : initProfileImageEditor();

export default initProfileImageEditor;
