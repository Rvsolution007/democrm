@extends('admin.layouts.app')

@section('title', 'My Profile')
@section('breadcrumb', 'My Profile')

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div>
                <h1 class="page-title">My Profile</h1>
                <p class="page-description">Update your name, email, and password</p>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div
            style="padding:12px 20px;background:#d4edda;color:#155724;border:1px solid #c3e6cb;border-radius:8px;margin-bottom:20px;font-size:14px">
            ✅ {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div
            style="padding:12px 20px;background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;border-radius:8px;margin-bottom:20px">
            <ul style="margin:0;padding-left:20px">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">
        <!-- Profile Info Card -->
        <div style="background:white;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.1);overflow:hidden">
            <div style="padding:20px 24px;border-bottom:1px solid #eee">
                <h3 style="margin:0;font-size:16px;font-weight:600;display:flex;align-items:center;gap:8px">
                    <i data-lucide="user" style="width:18px;height:18px;color:#f97316"></i>
                    Profile Information
                </h3>
                <p style="margin:4px 0 0;font-size:13px;color:#666">Update your name, email, and phone number</p>
            </div>
            <form method="POST" action="{{ route('admin.profile.update') }}">
                @csrf
                @method('PUT')
                <div style="padding:24px">
                    <!-- Avatar Preview -->
                    <div style="text-align:center;margin-bottom:24px">
                        <div
                            style="width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,#f97316,#ea580c);color:white;display:inline-flex;align-items:center;justify-content:center;font-size:28px;font-weight:700">
                            {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                        </div>
                        <p style="margin:8px 0 0;font-size:13px;color:#666">{{ auth()->user()->role->name ?? 'User' }}</p>
                    </div>

                    <div style="margin-bottom:16px">
                        <label style="display:block;margin-bottom:6px;font-weight:500;font-size:14px;color:#333">Full Name
                            *</label>
                        <input type="text" name="name" value="{{ old('name', auth()->user()->name) }}" required
                            style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:6px;font-size:14px;transition:border-color 0.2s"
                            onfocus="this.style.borderColor='#f97316'" onblur="this.style.borderColor='#ddd'">
                    </div>

                    <div style="margin-bottom:16px">
                        <label style="display:block;margin-bottom:6px;font-weight:500;font-size:14px;color:#333">Email
                            Address *</label>
                        <input type="email" name="email" value="{{ old('email', auth()->user()->email) }}" required
                            style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:6px;font-size:14px;transition:border-color 0.2s"
                            onfocus="this.style.borderColor='#f97316'" onblur="this.style.borderColor='#ddd'">
                    </div>

                    <div style="margin-bottom:20px">
                        <label
                            style="display:block;margin-bottom:6px;font-weight:500;font-size:14px;color:#333">Phone</label>
                        <input type="tel" name="phone" value="{{ old('phone', auth()->user()->phone) }}"
                            style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:6px;font-size:14px;transition:border-color 0.2s"
                            onfocus="this.style.borderColor='#f97316'" onblur="this.style.borderColor='#ddd'">
                    </div>

                    <button type="submit"
                        style="width:100%;padding:10px;background:linear-gradient(135deg,#f97316,#ea580c);color:white;border:none;border-radius:6px;font-size:14px;font-weight:600;cursor:pointer;transition:opacity 0.2s"
                        onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                        <i data-lucide="save"
                            style="width:16px;height:16px;display:inline-block;vertical-align:middle;margin-right:4px"></i>
                        Save Profile
                    </button>
                </div>
            </form>
        </div>

        <!-- Password Card -->
        <div style="background:white;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.1);overflow:hidden">
            <div style="padding:20px 24px;border-bottom:1px solid #eee">
                <h3 style="margin:0;font-size:16px;font-weight:600;display:flex;align-items:center;gap:8px">
                    <i data-lucide="lock" style="width:18px;height:18px;color:#f97316"></i>
                    Change Password
                </h3>
                <p style="margin:4px 0 0;font-size:13px;color:#666">Update your password to keep your account secure</p>
            </div>
            <form method="POST" action="{{ route('admin.profile.password') }}">
                @csrf
                @method('PUT')
                <div style="padding:24px">
                    <div style="margin-bottom:16px">
                        <label style="display:block;margin-bottom:6px;font-weight:500;font-size:14px;color:#333">Current
                            Password *</label>
                        <div style="position:relative">
                            <input type="password" name="current_password" id="current_password" required
                                style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:6px;font-size:14px"
                                onfocus="this.style.borderColor='#f97316'" onblur="this.style.borderColor='#ddd'">
                            <button type="button" onclick="togglePassword('current_password')"
                                style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;padding:4px;color:#666">
                                <i data-lucide="eye" style="width:16px;height:16px"></i>
                            </button>
                        </div>
                    </div>

                    <div style="margin-bottom:16px">
                        <label style="display:block;margin-bottom:6px;font-weight:500;font-size:14px;color:#333">New
                            Password *</label>
                        <div style="position:relative">
                            <input type="password" name="password" id="new_password" required minlength="6"
                                style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:6px;font-size:14px"
                                onfocus="this.style.borderColor='#f97316'" onblur="this.style.borderColor='#ddd'">
                            <button type="button" onclick="togglePassword('new_password')"
                                style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;padding:4px;color:#666">
                                <i data-lucide="eye" style="width:16px;height:16px"></i>
                            </button>
                        </div>
                        <p style="font-size:12px;color:#888;margin:4px 0 0">Minimum 6 characters</p>
                    </div>

                    <div style="margin-bottom:20px">
                        <label style="display:block;margin-bottom:6px;font-weight:500;font-size:14px;color:#333">Confirm New
                            Password *</label>
                        <div style="position:relative">
                            <input type="password" name="password_confirmation" id="confirm_password" required minlength="6"
                                style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:6px;font-size:14px"
                                onfocus="this.style.borderColor='#f97316'" onblur="this.style.borderColor='#ddd'">
                            <button type="button" onclick="togglePassword('confirm_password')"
                                style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;padding:4px;color:#666">
                                <i data-lucide="eye" style="width:16px;height:16px"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit"
                        style="width:100%;padding:10px;background:linear-gradient(135deg,#f97316,#ea580c);color:white;border:none;border-radius:6px;font-size:14px;font-weight:600;cursor:pointer;transition:opacity 0.2s"
                        onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                        <i data-lucide="key"
                            style="width:16px;height:16px;display:inline-block;vertical-align:middle;margin-right:4px"></i>
                        Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Account Info -->
    <div style="background:white;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.1);margin-top:24px;padding:24px">
        <h3 style="margin:0 0 16px;font-size:16px;font-weight:600;display:flex;align-items:center;gap:8px">
            <i data-lucide="info" style="width:18px;height:18px;color:#f97316"></i>
            Account Details
        </h3>
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px">
            <div style="padding:16px;background:#f8fafc;border-radius:8px">
                <p style="font-size:12px;color:#666;margin:0 0 4px;font-weight:500">User ID</p>
                <p style="font-size:16px;font-weight:600;margin:0">#{{ auth()->user()->id }}</p>
            </div>
            <div style="padding:16px;background:#f8fafc;border-radius:8px">
                <p style="font-size:12px;color:#666;margin:0 0 4px;font-weight:500">Role</p>
                <p style="font-size:16px;font-weight:600;margin:0">{{ auth()->user()->role->name ?? 'N/A' }}</p>
            </div>
            <div style="padding:16px;background:#f8fafc;border-radius:8px">
                <p style="font-size:12px;color:#666;margin:0 0 4px;font-weight:500">Status</p>
                <p style="font-size:16px;font-weight:600;margin:0;color:#16a34a">{{ ucfirst(auth()->user()->status) }}</p>
            </div>
            <div style="padding:16px;background:#f8fafc;border-radius:8px">
                <p style="font-size:12px;color:#666;margin:0 0 4px;font-weight:500">Member Since</p>
                <p style="font-size:16px;font-weight:600;margin:0">{{ auth()->user()->created_at->format('d M Y') }}</p>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function togglePassword(id) {
            var input = document.getElementById(id);
            input.type = input.type === 'password' ? 'text' : 'password';
        }
    </script>
@endpush