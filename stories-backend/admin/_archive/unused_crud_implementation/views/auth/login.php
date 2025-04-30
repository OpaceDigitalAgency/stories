<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="login-container">
                <div class="login-logo">
                    <h1>Stories Admin</h1>
                </div>
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Login</h4>
                    </div>
                    <div class="card-body">
                        <form action="<?php echo ADMIN_URL; ?>/login.php" method="post">
                            <div class="mb-3">
                                <label for="email" class="form-label required">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                                <div class="invalid-feedback">
                                    Please enter a valid email address.
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label required">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <div class="invalid-feedback">
                                    Please enter your password.
                                </div>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember" value="1">
                                <label class="form-check-label" for="remember">Remember me</label>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Login</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>