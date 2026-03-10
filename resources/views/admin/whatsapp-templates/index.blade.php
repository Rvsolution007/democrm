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
            <button class="btn-create-modern" onclick="openTemplateModal()">
                <i data-lucide="plus-circle" style="width: 20px; height: 20px;"></i> 
                <span>Create Template</span>
            </button>
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
                                    @if($template->media_path)
                                        <a href="{{ Storage::url($template->media_path) }}" target="_blank" class="media-preview-btn">
                                            <i data-lucide="external-link" style="width:14px;height:14px;"></i> View Asset
                                        </a>
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
                                        <button type="button" class="action-btn btn-edit-modern" onclick='editTemplate({{ json_encode($template) }})' title="Edit Template">
                                            <i data-lucide="edit-2" style="width: 16px; height: 16px;"></i>
                                        </button>
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
                    <label class="form-label fw-bold">Upload Media File</label>
                    
                    <!-- Current Media Preview (only visible when editing) -->
                    <div id="currentMediaPreview" style="display:none; margin-bottom: 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 15px; flex-direction: column; gap: 10px;">
                        <div style="display:flex; align-items:center; justify-content: space-between; width: 100%;">
                            <div style="display:flex; align-items:center; gap: 10px;">
                                <i data-lucide="paperclip" style="width:16px; height:16px; color:#64748b;"></i>
                                <div style="font-size: 0.85rem;">
                                    <span style="color:#64748b; font-weight:500;">Current file:</span>
                                    <span id="currentMediaName" style="color:#0f172a; font-weight:600; margin-left: 5px;"></span>
                                </div>
                            </div>
                            <a id="currentMediaLink" href="#" target="_blank" class="media-preview-btn" style="padding: 0.2rem 0.6rem; font-size: 0.75rem;">View File</a>
                        </div>
                        <div id="visualMediaContainer" style="display:none; text-align:center; padding-top: 10px; border-top: 1px dashed #e2e8f0;">
                            <!-- Dynamic preview content -->
                        </div>
                    </div>
                    
                    <input type="file" name="media_file" id="mediaFile" class="form-control form-input" accept="">
                    <small id="mediaHelpText" class="text-muted" style="font-size:12px; display:block; margin-top:4px;"></small>
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
    <script>
        function openTemplateModal() {
            document.getElementById('modalTitle').textContent = 'Create Template';
            document.getElementById('templateForm').action = "{{ route('admin.whatsapp-templates.store') }}";
            document.getElementById('formMethod').value = 'POST';
            document.getElementById('templateForm').reset();
            
            // Hide current media preview on new template creation
            document.getElementById('currentMediaPreview').style.display = 'none';

            // Remove simulated required asterisk
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
            
            // Handle current media preview
            const currentMediaPreview = document.getElementById('currentMediaPreview');
            const visualMediaContainer = document.getElementById('visualMediaContainer');
            if (template.media_path) {
                currentMediaPreview.style.display = 'flex';
                // Extract filename from path
                const fileName = template.media_path.split('/').pop();
                document.getElementById('currentMediaName').textContent = fileName;
                // Get the current APP_URL and format the link
                const baseUrl = window.location.origin;
                const fileUrl = baseUrl + '/storage/' + template.media_path;
                document.getElementById('currentMediaLink').href = fileUrl;
                
                // Show visual preview based on type
                visualMediaContainer.style.display = 'block';
                if (template.type === 'image') {
                    visualMediaContainer.innerHTML = `<img src="${fileUrl}" style="max-width: 100%; max-height: 250px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);" alt="Image Preview">`;
                } else if (template.type === 'video') {
                    visualMediaContainer.innerHTML = `<video src="${fileUrl}" controls style="max-width: 100%; max-height: 250px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);"></video>`;
                } else if (template.type === 'pdf') {
                    visualMediaContainer.innerHTML = `<iframe src="${fileUrl}" style="width: 100%; height: 250px; border: none; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);"></iframe>`;
                } else {
                    visualMediaContainer.style.display = 'none';
                }
            } else {
                currentMediaPreview.style.display = 'none';
            }

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
                if (document.getElementById('formMethod').value === 'POST') {
                    input.setAttribute('required', 'required');
                } else {
                    input.removeAttribute('required');
                }
            } else {
                container.style.display = 'none';
                input.removeAttribute('required');
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            // Initialize lucide icons if needed
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }

            // Client-side media preview
            document.getElementById('mediaFile').addEventListener('change', function(e) {
                const file = e.target.files[0];
                const currentMediaPreview = document.getElementById('currentMediaPreview');
                const visualMediaContainer = document.getElementById('visualMediaContainer');
                
                if (file) {
                    const fileUrl = URL.createObjectURL(file);
                    const type = document.getElementById('templateType').value;
                    
                    document.getElementById('currentMediaName').textContent = file.name;
                    document.getElementById('currentMediaLink').href = fileUrl;
                    
                    currentMediaPreview.style.display = 'flex';
                    visualMediaContainer.style.display = 'block';
                    
                    if (type === 'image' && file.type.startsWith('image/')) {
                        visualMediaContainer.innerHTML = `<img src="${fileUrl}" style="max-width: 100%; max-height: 250px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);" alt="Image Preview">`;
                    } else if (type === 'video' && file.type.startsWith('video/')) {
                        visualMediaContainer.innerHTML = `<video src="${fileUrl}" controls style="max-width: 100%; max-height: 250px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);"></video>`;
                    } else if (type === 'pdf' && file.type === 'application/pdf') {
                        visualMediaContainer.innerHTML = `<iframe src="${fileUrl}" style="width: 100%; height: 250px; border: none; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);"></iframe>`;
                    } else {
                        visualMediaContainer.style.display = 'none';
                    }
                }
            });
        });
    </script>
@endpush