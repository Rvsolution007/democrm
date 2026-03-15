@extends('admin.layouts.app')

@push('styles')
<style>
    /* Modern UI 2026 Styles for WhatsApp Templates */
    .page-header-modern {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        padding: 1.5rem 2rem;
        border-radius: 16px;
        box-shadow: 0 4px 20px -10px rgba(0,0,0,0.05);
        border: 1px solid rgba(226, 232, 240, 0.8);
    }
    .page-title-modern {
        font-size: 1.75rem;
        font-weight: 700;
        background: linear-gradient(135deg, #0f172a 0%, #334155 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin: 0;
        letter-spacing: -0.5px;
    }
    .btn-create-modern {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 12px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        box-shadow: 0 4px 15px -3px rgba(59, 130, 246, 0.4);
        transition: all 0.3s ease;
    }
    .btn-create-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px -5px rgba(59, 130, 246, 0.5);
        color: white;
    }
    .modern-card {
        background: #ffffff;
        border-radius: 20px;
        border: 1px solid rgba(226, 232, 240, 0.8);
        box-shadow: 0 10px 40px -10px rgba(0,0,0,0.03);
        overflow: hidden;
    }
    .modern-table th {
        background: #f8fafc;
        color: #64748b;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #e2e8f0;
    }
    .modern-table td {
        padding: 1.25rem 1.5rem;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        font-size: 0.95rem;
        color: #334155;
    }
    .modern-table tr:last-child td {
        border-bottom: none;
    }
    .modern-table tbody tr {
        transition: all 0.2s ease;
    }
    .modern-table tbody tr:hover {
        background-color: #f8fafc;
        transform: scale(1.001);
    }
    .type-badge {
        padding: 0.4rem 0.8rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: inline-flex;
        align-items: center;
    }
    .type-text { background: #eff6ff; color: #3b82f6; border: 1px solid #dbeafe; }
    .type-image { background: #fdf4ff; color: #d946ef; border: 1px solid #fae8ff; }
    .type-video { background: #fff7ed; color: #f97316; border: 1px solid #ffedd5; }
    .type-pdf { background: #fef2f2; color: #ef4444; border: 1px solid #fee2e2; }
    
    .action-btn {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: none;
        transition: all 0.2s ease;
        cursor: pointer;
    }
    .btn-edit-modern {
        background: #eff6ff;
        color: #3b82f6;
    }
    .btn-edit-modern:hover {
        background: #3b82f6;
        color: white;
        transform: translateY(-2px);
    }
    .btn-delete-modern {
        background: #fef2f2;
        color: #ef4444;
    }
    .btn-delete-modern:hover {
        background: #ef4444;
        color: white;
        transform: translateY(-2px);
    }
    .media-preview-btn {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
        padding: 0.4rem 0.8rem;
        border-radius: 8px;
        font-size: 0.8rem;
        font-weight: 500;
        transition: all 0.2s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
    }
    .media-preview-btn:hover {
        background: #e2e8f0;
        color: #0f172a;
    }
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
    }
    .empty-state-icon {
        width: 80px;
        height: 80px;
        background: #f8fafc;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1.5rem;
        color: #94a3b8;
        box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.06);
    }
    
    /* Modern Form Inputs inside Modal */
    #templateModal .form-input, #templateModal .form-select, #templateModal .form-textarea {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 0.75rem 1rem;
        font-size: 0.95rem;
        transition: all 0.2s;
        box-shadow: inset 0 1px 2px rgba(0,0,0,0.02);
    }
    #templateModal .form-input:focus, #templateModal .form-select:focus, #templateModal .form-textarea:focus {
        background-color: #ffffff;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        outline: none;
    }
    #templateModal .modal-header {
        border-bottom: 1px solid #f1f5f9;
        background: #fafaf9;
    }
    #templateModal .modal-footer {
        border-top: 1px solid #f1f5f9;
        background: #fafaf9;
    }
    #templateModal .btn-primary {
        background: #3b82f6;
        border-radius: 10px;
        padding: 0.6rem 1.25rem;
        font-weight: 500;
    }
    #templateModal .btn-secondary {
        background: #fff;
        border: 1px solid #e2e8f0;
        color: #475569;
        border-radius: 10px;
        padding: 0.6rem 1.25rem;
        font-weight: 500;
    }
</style>
@endpush

@section('title', 'WhatsApp Templates')

@section('content')
    <div class="container-fluid" style="padding: 1.5rem;">
        <!-- Modern Header -->
        <div class="page-header-modern">
            <div>
                <h2 class="page-title-modern">WhatsApp Templates</h2>
                <p class="text-muted mt-1 mb-0" style="font-size: 0.9rem;">Design and manage your rich media messaging templates</p>
            </div>
            @if(can('whatsapp-templates.global'))
                <button class="btn-create-modern" onclick="openTemplateModal()">
                    <i data-lucide="plus-circle" style="width: 20px; height: 20px;"></i> 
                    <span>Create Template</span>
                </button>
            @endif
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius: 12px; border: none; background: #ecfdf5; color: #047857; box-shadow: 0 4px 15px -5px rgba(0,0,0,0.05); padding: 1rem 1.5rem;">
                <div class="d-flex align-items-center">
                    <i data-lucide="check-circle" style="width: 20px; height: 20px; margin-right: 12px;"></i>
                    <strong>Success!</strong> &nbsp;{{ session('success') }}
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="padding: 1.25rem;"></button>
            </div>
        @endif

        <!-- Modern Data Card -->
        <div class="modern-card">
            <div class="table-responsive">
                <table class="table modern-table mb-0" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Template Name</th>
                            <th>Template Code</th>
                            <th>Creator</th>
                            <th>Format</th>
                            <th>Content Preview</th>
                            <th>Attachment</th>
                            <th>Created Date</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($templates as $template)
                            <tr>
                                <td>
                                    <div style="font-weight: 600; color: #1e293b;">{{ $template->name }}</div>
                                </td>
                                <td>
                                    <code style="background: #f1f5f9; padding: 3px 8px; border-radius: 6px; font-size: 0.8rem; font-weight: 600; color: #6366f1; letter-spacing: 1px;">{{ $template->template_code }}</code>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <div style="width: 28px; height: 28px; border-radius: 50%; background: #e2e8f0; display: flex; align-items: center; justify-content: center; color: #64748b; font-weight: 600; font-size: 0.75rem;">
                                            {{ substr($template->user->name ?? 'A', 0, 1) }}
                                        </div>
                                        <span style="font-size: 0.9rem; font-weight: 500; color: #475569;">
                                            {{ $template->user->name ?? 'System' }}
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <span class="type-badge type-{{ $template->type }}">
                                        @if($template->type == 'text') <i data-lucide="align-left" style="width:12px;height:12px;margin-bottom:2px;margin-right:2px"></i>
                                        @elseif($template->type == 'image') <i data-lucide="image" style="width:12px;height:12px;margin-bottom:2px;margin-right:2px"></i>
                                        @elseif($template->type == 'video') <i data-lucide="video" style="width:12px;height:12px;margin-bottom:2px;margin-right:2px"></i>
                                        @else <i data-lucide="file-text" style="width:12px;height:12px;margin-bottom:2px;margin-right:2px"></i> @endif
                                        {{ $template->type }}
                                    </span>
                                </td>
                                <td>
                                    <div style="color: #64748b; max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $template->message_text }}">
                                        {{ $template->message_text ?: 'No caption provided.' }}
                                    </div>
                                </td>
                                <td>
                                    @if(is_array($template->media_files) && count($template->media_files) > 0)
                                        <div style="display: flex; gap: 4px; flex-wrap: wrap;">
                                        @foreach($template->media_files as $idx => $media)
                                            <a href="{{ Storage::url($media['path']) }}" target="_blank" class="media-preview-btn" style="padding: 0.2rem 0.5rem; font-size: 0.7rem;" title="{{ $media['name'] }}">
                                                <i data-lucide="paperclip" style="width:12px;height:12px;"></i> #{{$idx + 1}}
                                            </a>
                                        @endforeach
                                        </div>
                                    @else
                                        <span class="badge" style="background: #f1f5f9; color: #94a3b8; font-weight: 400; padding: 0.4rem 0.8rem;">Text Only</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex align-items-center" style="font-size: 0.85rem; color: #64748b; font-weight: 500;">
                                        <i data-lucide="calendar" style="width:14px;height:14px; margin-right: 6px; opacity: 0.7;"></i>
                                        <span>{{ $template->created_at->format('d M, Y') }}</span>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end align-items-center gap-2">
                                        @if(can('whatsapp-templates.global') || $template->user_id == auth()->id())
                                            <button type="button" class="action-btn btn-edit-modern" onclick='editTemplate({{ json_encode($template) }})' title="Edit Template">
                                                <i data-lucide="edit-2" style="width: 16px; height: 16px;"></i>
                                            </button>
                                            <form action="{{ route('admin.whatsapp-templates.destroy', $template->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this template?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="action-btn btn-delete-modern" title="Delete Template">
                                                    <i data-lucide="trash-2" style="width: 16px; height: 16px;"></i>
                                                </button>
                                            </form>
                                        @else
                                            <span style="font-size:0.8rem;color:#94a3b8;font-style:italic;">Read-only</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        <div class="empty-state-icon">
                                            <i data-lucide="layout-template" style="width: 40px; height: 40px; stroke-width: 1.5;"></i>
                                        </div>
                                        <h4 style="color: #1e293b; font-weight: 600; font-size: 1.25rem;">No Templates Found</h4>
                                        <p style="color: #64748b; margin-top: 0.5rem; max-width: 400px; margin-left: auto; margin-right: auto;">Get started by creating your first WhatsApp message template. You can include text, images, videos, and PDF documents.</p>
                                        <button class="btn-create-modern mt-3" onclick="openTemplateModal()" style="padding: 0.6rem 1.25rem; font-size: 0.9rem;">
                                            Start Creating
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Template Create/Edit Modal -->
    <div id="modal-overlay" class="overlay" onclick="closeTemplateModal()"></div>
    <div class="modal" id="templateModal">
        <div class="modal-header">
            <h5 class="modal-title m-0" id="modalTitle">Create Template</h5>
            <button type="button" class="btn-close modal-close" onclick="closeTemplateModal()"
                style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
        </div>

        <form id="templateForm" action="{{ route('admin.whatsapp-templates.store') }}" method="POST"
            enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">

            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Template Name</label>
                    <input type="text" name="name" id="templateName" class="form-control form-input"
                        placeholder="e.g. Diwali Offer PDF" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Template Code</label>
                    <input type="text" name="template_code" id="templateCode" class="form-control form-input"
                        placeholder="Auto-generated" style="letter-spacing: 2px; font-weight: 600; text-transform: uppercase;">
                    <small class="text-muted" style="font-size: 11px;">Unique code for deduplication. Auto-generated if left empty. Same code = skip already sent recipients.</small>
                </div>

                <div class="mb-3" style="margin-top:15px;">
                    <label class="form-label fw-bold">Template Type</label>
                    <select name="type" id="templateType" class="form-control form-select" required
                        onchange="toggleMediaInput()">
                        <option value="text">Text Only</option>
                        <option value="image">Image + Caption</option>
                        <option value="video">Video + Caption</option>
                        <option value="pdf">PDF Document</option>
                    </select>
                    <div id="typeRulesBox" style="margin-top: 8px; padding: 10px 14px; border-radius: 8px; font-size: 0.8rem; line-height: 1.6; display: none;"></div>
                </div>

                <div class="mb-3" id="mediaInputContainer" style="display:none; margin-top:15px;">
                    <label class="form-label fw-bold">Upload Media Files (Drag to reorder)</label>
                    <input type="file" name="media_files[]" id="mediaFile" class="form-control form-input" accept="" multiple>
                    <small id="mediaHelpText" class="text-muted" style="font-size:12px; display:block; margin-top:4px; max-width:100%; word-break:break-word;"></small>
                    
                    <input type="hidden" name="existing_media" id="existingMediaInput" value="[]">

                    <div style="margin-top: 15px;">
                        <ul id="sortableMediaList" style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:8px;">
                            <!-- File preview items injected via JS -->
                        </ul>
                    </div>
                </div>

                <div class="mb-3" style="margin-top:15px;">
                    <label class="form-label fw-bold">Message Text / Media Caption</label>
                    <textarea name="message_text" id="messageText" class="form-control form-textarea" rows="5"
                        placeholder="Type your WhatsApp message here..."></textarea>
                </div>
            </div>

            <div class="modal-footer" style="padding:15px; border-top:1px solid #eee; gap:10px;">
                <button type="button" class="btn btn-secondary" onclick="closeTemplateModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Template</button>
            </div>
        </form>
    </div>

@endsection

@push('scripts')
    <!-- SortableJS for drag-and-drop -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <script>
        let selectedFiles = []; // Holds new File objects
        let existingFiles = []; // Holds existing JSON objects from DB
        
        // Setup Sortable
        let sortableList;
        document.addEventListener('DOMContentLoaded', function () {
            const listEl = document.getElementById('sortableMediaList');
            sortableList = new Sortable(listEl, {
                animation: 150,
                handle: '.drag-handle',
                ghostClass: 'sortable-ghost',
                onEnd: function (evt) {
                    // Update internal arrays when user finishes dragging
                    const type = evt.item.dataset.type;
                    const oldIndex = evt.oldIndex;
                    const newIndex = evt.newIndex;
                    
                    // The DOM is already reordered, we just need to reconstruct our combined virtual array layout
                    syncArraysFromDOM();
                }
            });
        });

        function syncArraysFromDOM() {
            const items = document.querySelectorAll('#sortableMediaList li');
            let newExisting = [];
            let newSelected = [];
            
            items.forEach(item => {
                if (item.dataset.type === 'existing') {
                    const id = item.dataset.id;
                    const f = existingFiles.find(ex => ex.path === id);
                    if (f) newExisting.push(f);
                } else {
                    const id = item.dataset.id;
                    const f = selectedFiles.find(sel => sel.name === id);
                    if (f) newSelected.push(f);
                }
            });
            
            existingFiles = newExisting;
            selectedFiles = newSelected;
            document.getElementById('existingMediaInput').value = JSON.stringify(existingFiles);
        }
        function openTemplateModal() {
            document.getElementById('modalTitle').textContent = 'Create Template';
            document.getElementById('templateForm').action = "{{ route('admin.whatsapp-templates.store') }}";
            document.getElementById('formMethod').value = 'POST';
            document.getElementById('templateForm').reset();
            
            selectedFiles = [];
            existingFiles = [];
            document.getElementById('existingMediaInput').value = '[]';
            renderMediaList();

            document.getElementById('templateType').value = 'text';
            toggleMediaInput();

            openModal('templateModal');
        }

        function closeTemplateModal() {
            closeModal('templateModal');
        }

        function editTemplate(template) {
            document.getElementById('modalTitle').textContent = 'Edit Template';
            document.getElementById('templateForm').action = "{{ route('admin.whatsapp-templates.index') }}/" + template.id;
            document.getElementById('formMethod').value = 'PUT';

            document.getElementById('templateName').value = template.name;
            document.getElementById('templateCode').value = template.template_code || '';
            document.getElementById('templateType').value = template.type;
            document.getElementById('messageText').value = template.message_text;
            
            selectedFiles = [];
            existingFiles = Array.isArray(template.media_files) ? template.media_files : [];
            document.getElementById('existingMediaInput').value = JSON.stringify(existingFiles);
            
            // Optional: reset file input to clear any previous local files
            document.getElementById('mediaFile').value = '';
            
            renderMediaList();
            toggleMediaInput();

            openModal('templateModal');
        }

        function toggleMediaInput() {
            const type = document.getElementById('templateType').value;
            const container = document.getElementById('mediaInputContainer');
            const input = document.getElementById('mediaFile');
            const rulesBox = document.getElementById('typeRulesBox');
            const helpText = document.getElementById('mediaHelpText');

            const rules = {
                text: {
                    show: false,
                    color: '#eff6ff',
                    border: '#bfdbfe',
                    text: '📝 <b>Text Only</b> — Type your message below. No media file needed. Supports emojis and multi-line text.',
                },
                image: {
                    show: true,
                    accept: '.jpg,.jpeg,.png,.webp',
                    help: 'Formats: JPG, JPEG, PNG, WEBP — Max 5MB',
                    color: '#fdf4ff',
                    border: '#f0abfc',
                    text: '🖼️ <b>Image Rules:</b><br>• Formats: <b>JPG, JPEG, PNG, WEBP</b><br>• Max size: <b>5 MB</b><br>• Recommended: Square or landscape images work best<br>• Caption text will appear below the image',
                },
                video: {
                    show: true,
                    accept: '.mp4,.3gp',
                    help: 'Formats: MP4, 3GP — Max 10MB',
                    color: '#fff7ed',
                    border: '#fdba74',
                    text: '🎬 <b>Video Rules:</b><br>• Formats: <b>MP4, 3GP</b><br>• Max size: <b>10 MB</b> (keep short for WhatsApp)<br>• Recommended: Under 30 seconds for best delivery<br>• Caption text will appear below the video',
                },
                pdf: {
                    show: true,
                    accept: '.pdf',
                    help: 'Format: PDF only — Max 10MB',
                    color: '#fef2f2',
                    border: '#fca5a5',
                    text: '📄 <b>PDF Document Rules:</b><br>• Format: <b>PDF only</b><br>• Max size: <b>10 MB</b><br>• File name will be sent as-is to recipient<br>• Caption text will appear as message with the document',
                },
            };

            const rule = rules[type] || rules.text;

            // Show rules box for all types
            rulesBox.style.display = 'block';
            rulesBox.style.background = rule.color;
            rulesBox.style.border = '1px solid ' + rule.border;
            rulesBox.innerHTML = rule.text;

            if (rule.show) {
                container.style.display = 'block';
                input.setAttribute('accept', rule.accept);
                helpText.textContent = rule.help;
            } else {
                container.style.display = 'none';
                input.removeAttribute('required');
            }
        }

        // File Selection handling
        document.getElementById('mediaFile').addEventListener('change', function(e) {
            const files = Array.from(e.target.files);
            selectedFiles = [...selectedFiles, ...files];
            
            // Clear the actual input so user can select the same file again if desired
            e.target.value = '';
            
            renderMediaList();
        });

        function removeMedia(type, id) {
            if (type === 'existing') {
                existingFiles = existingFiles.filter(f => f.path !== id);
                document.getElementById('existingMediaInput').value = JSON.stringify(existingFiles);
            } else {
                selectedFiles = selectedFiles.filter(f => f.name !== id);
            }
            renderMediaList();
        }

        function renderMediaList() {
            const listEl = document.getElementById('sortableMediaList');
            listEl.innerHTML = '';
            
            // Since we combined arrays virtually on submit using syncArraysFromDOM, we just render them sequentially here
            let html = '';
            const baseUrl = window.location.origin + '/storage/';
            
            existingFiles.forEach((file, index) => {
                html += createMediaCard(file.name, baseUrl + file.path, 'existing', file.path);
            });
            
            selectedFiles.forEach((file, index) => {
                const url = URL.createObjectURL(file);
                html += createMediaCard(file.name, url, 'new', file.name);
            });
            
            listEl.innerHTML = html;
            
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }

        function createMediaCard(fileName, fileUrl, type, id) {
            const tType = document.getElementById('templateType').value;
            let previewHtml = '';
            
            if (tType === 'image') {
                previewHtml = `<img src="${fileUrl}" style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px;">`;
            } else if (tType === 'video') {
                previewHtml = `<video src="${fileUrl}" style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px;" muted></video>`;
            } else {
                previewHtml = `<div style="width: 50px; height: 50px; background: #e2e8f0; border-radius: 6px; display: flex; align-items: center; justify-content: center;"><i data-lucide="file-text" style="color: #64748b;"></i></div>`;
            }

            return `
                <li data-type="${type}" data-id="${id}" style="display: flex; align-items: center; justify-content: space-between; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px; cursor: default;">
                    <div style="display: flex; align-items: center; gap: 12px; flex: 1; overflow: hidden;">
                        <span class="drag-handle" style="cursor: grab; color: #cbd5e1; padding: 0 5px;">
                            <i data-lucide="grip-vertical" style="width: 20px; height: 20px;"></i>
                        </span>
                        ${previewHtml}
                        <div style="font-size: 0.85rem; font-weight: 600; color: #334155; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 200px;">
                            ${fileName} 
                            <span style="font-size: 0.7rem; font-weight: normal; color: #94a3b8; display: block;">${type === 'existing' ? '(Saved)' : '(New)'}</span>
                        </div>
                    </div>
                    <div>
                        <button type="button" onclick="removeMedia('${type}', '${id}')" style="background: none; border: none; color: #ef4444; cursor: pointer; padding: 5px; border-radius: 5px; display: inline-flex;">
                            <i data-lucide="x" style="width: 16px; height: 16px;"></i>
                        </button>
                    </div>
                </li>
            `;
        }

        // Intercept form submission to rebuild the FileList according to current sequence
        document.getElementById('templateForm').addEventListener('submit', function(e) {
            // First we ensure the logical array sequence matches the physical DOM sequence
            syncArraysFromDOM();
            
            // Now build a new DataTransfer for the actual file input representing ONLY the new "selectedFiles"
            // Wait, existing files do not need re-uploading, they are preserved via the existing_media array
            // The order of the combined items indicates the FINAL sending order.
            // Our backend needs to know the absolute final arrangement of EVERYTHING
            // So we submit "existing_media[]" array order AND we submit "media_files[]" array order
            // However, typical HTML forms can't easily interleave multipart files with standard text arrays for ordering.
            // SOLUTION: We'll send a virtual JSON array describing the exact structural order.
            
            let structuralMap = [];
            const items = document.querySelectorAll('#sortableMediaList li');
            
            const dt = new DataTransfer();
            items.forEach((item, index) => {
                if (item.dataset.type === 'existing') {
                    structuralMap.push({ source: 'existing', identifier: item.dataset.id });
                } else {
                    const f = selectedFiles.find(sel => sel.name === item.dataset.id);
                    if (f) {
                        structuralMap.push({ source: 'new', identifier: f.name });
                        dt.items.add(f); // Add to real payload
                    }
                }
            });
            
            // Pass structural map to backend
            const mapInput = document.createElement('input');
            mapInput.type = 'hidden';
            mapInput.name = 'structural_order';
            mapInput.value = JSON.stringify(structuralMap);
            this.appendChild(mapInput);
            
            // Reset the physical input's files
            document.getElementById('mediaFile').files = dt.files;
            
            // Require at least 1 image/file if creating explicitly (if mandatory)
            // Left to the backend for final verification
        });
        
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    </script>
@endpush