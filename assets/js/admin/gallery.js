const $ = require('jquery');

let GalleryEventList = function ($wrapper) {
    this.$root = $wrapper;
    this.dataSource = $wrapper.attr('data-source-input');
    this.dispatcher = $({});

    this.init();
    this.draw();
    this.$root.on(
        'click',
        '.nav-item-action:not(.disabled)',
        this._processNavigationAction.bind(this)
    );
    this.$root.on(
        'click',
        '.nav-item',
        this._editItem.bind(this)
    );
    this.$root.on(
        'click',
        '.edit-item-form button',
        this._processInputFormAction.bind(this)
    );
};

$.extend(GalleryEventList.prototype, {
    init() {
        const srcJSON = $(this.dataSource).val();
        this.eventList = JSON.parse(srcJSON);
    },
    draw() {
        this.$root.empty();
        this._buildHTML(this.$root[ 0 ], this.eventList);
    },
    addNew(name = null) {
        this._createEntry(name);
        this._synchroniseData()
        this.draw();
    },
    _createEntry(name = null) {
        this.eventList.push({
            'name': name ?? 'Neues Event',
            'count': 0,
        });
    },
    _buildHTML(baseElement, items) {
        let hasPrevItem = false;
        let hasNextItem = true;

        for (const [i, item] of items.entries()) {
            if (i === items.length - 1) {
                hasNextItem = false;
            }
            let ele = this._buildHTMLItem(item, i, hasPrevItem, hasNextItem);
            baseElement.appendChild(ele);
            hasPrevItem = true;
        }
    },
    _buildHTMLItem(item, index, hasPrevItem = false, hasNextItem = false) {
        let li = document.createElement("LI");
        li.setAttribute("class", "list-group-item d-flex");
        li.setAttribute("data-index", index);

        li.appendChild(this._buildActionElement("fas fa-arrow-up", "up", hasPrevItem, "Up"));
        li.appendChild(this._buildActionElement("fas fa-arrow-down", "down", hasNextItem, "Down"));

        let label = document.createElement("SPAN");
        label.setAttribute("class", "w-100 nav-item-label");
        let span = document.createElement('SPAN');
        span.setAttribute('class', 'nav-item pl-2');
        span.setAttribute("data-value", item.name);
        span.setAttribute("data-count", item.count);
        span.textContent = item.name;
        let span2 = document.createElement('SPAN')
        span2.setAttribute('class', 'text-muted pl-1');
        span2.textContent = "(" + item.count + ")";
        let i = document.createElement("I");
        i.setAttribute("class", "fas fa-edit pl-2 edit-img text-dark");
        span.appendChild(span2);
        span.appendChild(i);
        label.appendChild(span);
        li.appendChild(label);
        return li;
    },
    _buildActionElement(itemImgClass, action, enableItem = false, title = "") {
        let i = document.createElement("I");
        i.setAttribute("class", itemImgClass + " fa-fw");

        let actionItem = null;
        if (enableItem) {
            actionItem = document.createElement("A");
            actionItem.setAttribute("href", "#");
            actionItem.setAttribute("class", "nav-item-action");
            actionItem.setAttribute("title", title);
            actionItem.setAttribute("data-action", action);
        } else {
            actionItem = document.createElement("SPAN");
            actionItem.setAttribute("class", "nav-item-action text-disabled disabled");
        }
        actionItem.appendChild(i);

        return actionItem;
    },
    _processNavigationAction(e) {
        e.preventDefault();
        let $actionButton = $(e.currentTarget);
        let action = $actionButton.data("action");
        let index = Number($actionButton.parent().data("index"));
        switch (action) {
            case "up":
                [this.eventList[index-1], this.eventList[index]] = [this.eventList[index], this.eventList[index-1]];
                break;
            case "down":
                [this.eventList[index+1], this.eventList[index]] = [this.eventList[index], this.eventList[index+1]];
                break;
        }
        this._synchroniseData();
        this.draw();
    },
    _synchroniseData() {
        this.dispatcher.trigger("changed");
        const json = JSON.stringify(this.eventList);
        $(this.dataSource).val(json);
    },
    _editItem(e) {
        e.preventDefault();

        $("form.edit-item-form").each((_, element) => {
            this._toggleItemEditMode($(element));
        });

        let $item = $(e.currentTarget);
        this._toggleItemEditMode($item);
    },
    _toggleItemEditMode($item) {
        if ($item.is('form')) {
            $item.prev().show();
            $item.remove();
        } else {
            let $form = this._getInputForm($item.data("value"), Number($item.data("count") === 0 && this.eventList.length > 1));
            $item.hide();
            $item.after($form);
        }
    },
    _getInputForm(inputVal, addDelete = false) {
        let $form = $('<form></form>', {"class": "edit-item-form form-inline d-inline-block pl-2 w-100"});

        let $inputGroup = $('<div></div>', {"class": "input-group input-group-sm"});
        $("<input>", {"type": "text", "class": "form-control edit-item-value", "value": inputVal}).appendTo($inputGroup);

        let $inputGroupAppend = $('<div></div>', {"class": "input-group-append"});
        $("<button type='submit' title='Save Changes' class='btn btn-outline-primary'><i class='fas fa-check fa-xs px-1'></i></button>").appendTo($inputGroupAppend);
        $("<button type='reset' title='Cancel' class='btn btn-outline-secondary'><i class='fas fa-times fa-xs px-1'></i></button>").appendTo($inputGroupAppend);
        if (addDelete) {
            $("<button type='delete' title='Delete Item' class='btn btn-outline-danger'><i class='fas fa-trash-alt fa-xs px-1'></i></button>").appendTo($inputGroupAppend);
        }

        $inputGroupAppend.appendTo($inputGroup);
        $inputGroup.appendTo($form);

        return $form;
    },
    _processInputFormAction(e) {
        e.preventDefault();
        let $btn = $(e.currentTarget);
        let $form = $btn.parents("form:first");
        const index = $form.parents(".list-group-item:first").data("index");

        if ($btn.attr("type") === "submit") {
            this.eventList[index].name = $form.find("input.edit-item-value").val();
            this._synchroniseData();
        } else if ($btn.attr("type") === "delete") {
            if (Number(this.eventList[index].count) === 0)
                this.eventList.splice(index, 1);
            this._synchroniseData();
        }
        this.draw();
    },
});

// Gallery Bulk Upload Handler
class GalleryBulkUpload {
    constructor() {
        this.selectedFiles = [];
        this.init();
    }

    init() {
        // Get DOM elements
        this.dropZone = document.getElementById('dropZone');
        this.fileInput = document.getElementById('fileInput');
        this.selectFilesBtn = document.getElementById('selectFilesBtn');
        this.eventSelect = document.getElementById('eventSelect');
        this.newEventBtn = document.getElementById('newEventBtn');
        this.newEventField = document.getElementById('newEventField');
        this.newEventInput = document.getElementById('newEvent');
        this.filesList = document.getElementById('filesList');
        this.filesContainer = document.getElementById('filesContainer');
        this.fileCount = document.getElementById('fileCount');
        this.clearBtn = document.getElementById('clearBtn');
        this.uploadBtn = document.getElementById('uploadBtn');
        this.uploadProgress = document.getElementById('uploadProgress');
        this.progressBar = document.getElementById('progressBar');
        this.progressText = document.getElementById('progressText');
        this.uploadResults = document.getElementById('uploadResults');
        this.form = document.getElementById('bulkUploadForm');

        this.bindEvents();
    }

    bindEvents() {
        // File selection
        this.selectFilesBtn?.addEventListener('click', () => this.fileInput.click());
        
        // Event management
        this.newEventBtn?.addEventListener('click', () => this.toggleNewEventField());
        this.eventSelect?.addEventListener('change', () => this.updateUploadButton());
        this.newEventInput?.addEventListener('input', () => this.updateUploadButton());
        
        // File management
        this.clearBtn?.addEventListener('click', () => this.clearFiles());
        
        // Drag & Drop
        this.dropZone?.addEventListener('dragover', (e) => this.handleDragOver(e));
        this.dropZone?.addEventListener('dragleave', (e) => this.handleDragLeave(e));
        this.dropZone?.addEventListener('drop', (e) => this.handleDrop(e));
        
        this.fileInput?.addEventListener('change', (e) => this.handleFileSelect(e));
        
        // Form submission
        this.form?.addEventListener('submit', (e) => this.handleSubmit(e));
        
        // Make removeFile globally accessible
        window.removeFile = (index) => this.removeFile(index);
    }

    toggleNewEventField() {
        if (this.newEventField.style.display === 'none') {
            this.newEventField.style.display = 'block';
            this.newEventBtn.innerHTML = '<i class="fas fa-times"></i> Abbrechen';
            this.eventSelect.disabled = true;
            this.eventSelect.value = '';
        } else {
            this.newEventField.style.display = 'none';
            this.newEventBtn.innerHTML = '<i class="fas fa-plus"></i> Neues Event';
            this.eventSelect.disabled = false;
            this.newEventInput.value = '';
        }
        this.updateUploadButton();
    }

    handleDragOver(e) {
        e.preventDefault();
        this.dropZone.classList.add('border-success');
    }

    handleDragLeave(e) {
        e.preventDefault();
        this.dropZone.classList.remove('border-success');
    }

    handleDrop(e) {
        e.preventDefault();
        this.dropZone.classList.remove('border-success');
        
        const files = Array.from(e.dataTransfer.files).filter(file => 
            file.type.startsWith('image/')
        );
        
        this.addFiles(files);
    }

    handleFileSelect(e) {
        this.addFiles(Array.from(e.target.files));
    }

    addFiles(files) {
        files.forEach(file => {
            if (file.type.startsWith('image/') && !this.selectedFiles.find(f => f.name === file.name && f.size === file.size)) {
                this.selectedFiles.push(file);
            }
        });
        this.updateFilesList();
        this.updateUploadButton();
    }

    removeFile(index) {
        this.selectedFiles.splice(index, 1);
        this.updateFilesList();
        this.updateUploadButton();
    }

    clearFiles() {
        this.selectedFiles = [];
        this.fileInput.value = '';
        this.updateFilesList();
        this.updateUploadButton();
    }

    updateFilesList() {
        if (this.selectedFiles.length === 0) {
            this.filesList.style.display = 'none';
            return;
        }

        this.filesList.style.display = 'block';
        this.fileCount.textContent = this.selectedFiles.length;
        
        this.filesContainer.innerHTML = '';
        this.selectedFiles.forEach((file, index) => {
            const fileElement = document.createElement('div');
            fileElement.className = 'col-md-3';
            fileElement.innerHTML = `
                <div class="card">
                    <div class="card-body p-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-truncate me-2" title="${file.name}">${file.name}</small>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeFile(${index})">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <small class="text-muted">${(file.size / 1024 / 1024).toFixed(2)} MB</small>
                    </div>
                </div>
            `;
            this.filesContainer.appendChild(fileElement);
        });

        this.clearBtn.disabled = this.selectedFiles.length === 0;
    }

    updateUploadButton() {
        const hasEvent = this.eventSelect?.value || this.newEventInput?.value.trim();
        const hasFiles = this.selectedFiles.length > 0;
        if (this.uploadBtn) {
            this.uploadBtn.disabled = !hasEvent || !hasFiles;
        }
    }

    handleSubmit(e) {
        e.preventDefault();
        this.uploadFiles();
    }

    uploadFiles() {
        if (this.selectedFiles.length === 0) {
            alert('Bitte wählen Sie mindestens eine Datei aus.');
            return;
        }

        const eventName = (this.newEventInput?.value.trim() || this.eventSelect?.value || '').trim();
        
        if (!eventName) {
            alert('Bitte wählen Sie ein Event aus oder geben Sie einen neuen Event-Namen ein.');
            return;
        }

        if (eventName.length < 3) {
            alert('Der Event-Name muss mindestens 3 Zeichen lang sein.');
            return;
        }

        this.uploadProgress.style.display = 'block';
        this.uploadBtn.disabled = true;
        this.clearBtn.disabled = true;

        const formData = new FormData();
        formData.append('event', eventName);
        
        this.selectedFiles.forEach((file) => {
            formData.append('images[]', file);
        });

        // Show indeterminate progress
        this.progressBar.classList.add('progress-bar-striped', 'progress-bar-animated');
        this.progressBar.style.width = '100%';
        this.progressText.textContent = `${this.selectedFiles.length} Bilder werden hochgeladen...`;

        // Get upload URL from window object or form action
        const uploadUrl = window.galleryBulkUploadUrl || this.form.action;

        // Start upload
        fetch(uploadUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => this.handleUploadResponse(data))
        .catch(error => this.handleUploadError(error));
    }

    handleUploadResponse(data) {
        this.progressText.textContent = 'Upload abgeschlossen';
        
        let resultHtml = '';
        if (data.success) {
            resultHtml += `
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Erfolgreich!</strong> ${data.uploaded} Bilder wurden hochgeladen.
                </div>
            `;
            
            if (data.errors && data.errors.length > 0) {
                resultHtml += `
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Warnungen:</strong>
                        <ul class="mb-0 mt-2">
                `;
                data.errors.forEach(error => {
                    resultHtml += `<li>${error}</li>`;
                });
                resultHtml += `</ul></div>`;
            }

            // Reset form after success
            setTimeout(() => {
                this.selectedFiles = [];
                this.fileInput.value = '';
                this.updateFilesList();
                this.uploadProgress.style.display = 'none';
                this.uploadBtn.disabled = false;
                this.clearBtn.disabled = false;
                this.updateUploadButton();
            }, 2000);
        } else {
            resultHtml = `
                <div class="alert alert-danger">
                    <i class="fas fa-times-circle me-2"></i>
                    <strong>Fehler:</strong> ${data.error || 'Upload fehlgeschlagen'}
                </div>
            `;
            this.uploadBtn.disabled = false;
            this.clearBtn.disabled = false;
        }
        
        this.uploadResults.innerHTML = resultHtml;
        this.uploadResults.style.display = 'block';
    }

    handleUploadError(error) {
        console.error('Upload error:', error);
        
        this.uploadResults.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-times-circle me-2"></i>
                <strong>Fehler:</strong> Upload fehlgeschlagen
            </div>
        `;
        this.uploadResults.style.display = 'block';
        this.uploadBtn.disabled = false;
        this.clearBtn.disabled = false;
    }
}

// Utility function for unsaved changes warning
let showAreYouSureFunction = function (e) {
    const confirmationMessage = "You have unchanged things!";
    (e || window.event).returnValue = confirmationMessage;
    return confirmationMessage;
};

// Form Validation Handler
class FormValidationHandler {
    constructor() {
        this.init();
    }

    init() {
        window.addEventListener('load', () => {
            const forms = document.getElementsByClassName('needs-validation');
            Array.prototype.filter.call(forms, (form) => {
                form.addEventListener('submit', (event) => {
                    if (form.checkValidity() === false) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        }, false);
    }
}

// Gallery Event Filtering for Gallery Index
class GalleryEventFilter {
    constructor() {
        this.init();
    }

    init() {
        // Make functions globally available for onclick handlers
        window.showAllEvents = this.showAllEvents.bind(this);
        window.showEvent = this.showEvent.bind(this);
    }

    showAllEvents() {
        document.querySelectorAll('.event-section').forEach(section => {
            section.style.display = 'block';
        });
        this.setActiveButton('all');
    }

    showEvent(eventName) {
        document.querySelectorAll('.event-section').forEach(section => {
            if (section.dataset.event === eventName) {
                section.style.display = 'block';
            } else {
                section.style.display = 'none';
            }
        });
        this.setActiveButton(eventName);
    }

    setActiveButton(activeEvent) {
        document.querySelectorAll('.list-group-item').forEach(btn => {
            btn.classList.remove('active');
            if ((activeEvent === 'all' && btn.textContent.includes('Alle Events')) ||
                (activeEvent !== 'all' && btn.onclick && btn.onclick.toString().includes(`'${activeEvent}'`))) {
                btn.classList.add('active');
            }
        });
    }
}

// Initialize components when DOM is ready
$(document).ready(() => {
    // Initialize Gallery Events Management if elements exist
    if ($('#eventList').length) {
        let eventList = new GalleryEventList($('#eventList'));
        let changeEvent = null;

        eventList.dispatcher.on("changed", function (e) {
            if (changeEvent === null) {
                window.addEventListener("beforeunload", showAreYouSureFunction);
            }
        });

        $("#event_edit_form").on("submit", function (_) {
            window.removeEventListener("beforeunload", showAreYouSureFunction);
        });

        $("#new").on("click", function(e) {
            e.preventDefault();
            eventList.addNew('Neues Event');
        });
    }

    // Initialize Bulk Upload if elements exist
    if (document.getElementById('bulkUploadForm')) {
        new GalleryBulkUpload();
    }

    // Initialize Gallery Event Filtering for index page
    if (document.querySelector('.event-section')) {
        new GalleryEventFilter();
    }

    // Initialize Form Validation for any form with needs-validation class
    if (document.querySelector('.needs-validation')) {
        new FormValidationHandler();
    }
});