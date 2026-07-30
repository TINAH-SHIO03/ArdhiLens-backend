<div class="ardhi-auth-shell" wire:ignore.self>
    <div class="ardhi-auth-header" aria-hidden="true">
        <div class="ardhi-auth-orb ardhi-auth-orb--one"></div>
        <div class="ardhi-auth-orb ardhi-auth-orb--two"></div>
    </div>

    <div class="container ardhi-auth-container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-5">
                <div class="ardhi-auth-panel" id="ardhiAuthPanel">
                    <p class="ardhi-auth-eyebrow">
                        <i class="bi bi-shield-check me-1"></i>
                        ADMIN ACCESS
                    </p>
                    <h1 class="ardhi-auth-brand">ArdhiLens</h1>
                    <h2 class="ardhi-auth-title">{{ $this->getHeading() }}</h2>
                    <p class="ardhi-auth-subtitle">{{ $this->getSubheading() }}</p>

                    <div class="ardhi-auth-card">
                        <form wire:submit="authenticate" class="ardhi-auth-form" novalidate>
                            <div class="mb-3">
                                <label for="data.email" class="form-label ardhi-label">Email</label>
                                <div class="input-group ardhi-input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-envelope"></i>
                                    </span>
                                    <input
                                        wire:model="data.email"
                                        type="email"
                                        id="data.email"
                                        class="form-control @error('data.email') is-invalid @enderror"
                                        placeholder="you@example.com"
                                        autocomplete="username"
                                        autofocus
                                        required
                                    >
                                </div>
                                @error('data.email')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="data.password" class="form-label ardhi-label">Password</label>
                                <div class="input-group ardhi-input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-lock"></i>
                                    </span>
                                    <input
                                        wire:model="data.password"
                                        type="password"
                                        id="data.password"
                                        class="form-control ardhi-password-input @error('data.password') is-invalid @enderror"
                                        placeholder="********"
                                        autocomplete="current-password"
                                        required
                                    >
                                    <button
                                        type="button"
                                        class="btn btn-outline-secondary ardhi-toggle-password"
                                        id="togglePassword"
                                        aria-label="Show password"
                                    >
                                        <i class="bi bi-eye-slash" id="togglePasswordIcon"></i>
                                    </button>
                                </div>
                                @error('data.password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div class="form-check">
                                    <input
                                        wire:model="data.remember"
                                        class="form-check-input"
                                        type="checkbox"
                                        id="data.remember"
                                    >
                                    <label class="form-check-label" for="data.remember">
                                        Remember me
                                    </label>
                                </div>
                            </div>

                            <button
                                type="submit"
                                class="btn ardhi-btn-primary w-100"
                                wire:loading.attr="disabled"
                            >
                                <span wire:loading.remove wire:target="authenticate">
                                    Sign in
                                </span>
                                <span wire:loading wire:target="authenticate" class="d-inline-flex align-items-center gap-2">
                                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                    Signing in…
                                </span>
                            </button>
                        </form>
                    </div>

                    <p class="ardhi-auth-footer">
                        Web admin only · Land registry · Seller KYC · Plot NIN linking
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
