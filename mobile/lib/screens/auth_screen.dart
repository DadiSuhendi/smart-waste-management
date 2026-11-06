import 'package:flutter/material.dart';

import '../core/session.dart';
import '../widgets/common.dart';

class AuthScreen extends StatefulWidget {
  final Session session;
  const AuthScreen({required this.session, super.key});
  @override
  State<AuthScreen> createState() => _AuthScreenState();
}

class _AuthScreenState extends State<AuthScreen> {
  final form = GlobalKey<FormState>();
  final name = TextEditingController(),
      email = TextEditingController(),
      password = TextEditingController(),
      confirmation = TextEditingController();
  bool register = false, busy = false, hide = true;
  String? error;
  @override
  void dispose() {
    for (final c in [name, email, password, confirmation]) {
      c.dispose();
    }
    super.dispose();
  }

  Future<void> submit() async {
    if (!form.currentState!.validate()) return;
    setState(() {
      busy = true;
      error = null;
    });
    try {
      await widget.session.authenticate({
        'email': email.text.trim(),
        'password': password.text,
        if (register) 'name': name.text.trim(),
        if (register) 'password_confirmation': confirmation.text,
      }, register: register);
    } catch (e) {
      if (mounted) setState(() => error = e.toString());
    } finally {
      if (mounted) setState(() => busy = false);
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    body: SafeArea(
      child: Center(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(28),
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 420),
            child: Form(
              key: form,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  const Align(alignment: Alignment.centerLeft, child: Brand()),
                  const SizedBox(height: 48),
                  Text(
                    register
                        ? 'Mulai langkah baik Anda.'
                        : 'Selamat datang kembali.',
                    style: const TextStyle(
                      fontSize: 28,
                      fontWeight: FontWeight.w600,
                      letterSpacing: -.8,
                    ),
                  ),
                  const SizedBox(height: 10),
                  Text(
                    register ? 'Buat akun dan bantu jaga lingkungan sekitar.' : 'Masuk untuk melaporkan dan memantau penanganan sampah.',
                    style: const TextStyle(color: muted, height: 1.6),
                  ),
                  const SizedBox(height: 30),
                  if (error != null) ErrorNotice(error!),
                  if (register) ...[
                    TextFormField(
                      controller: name,
                      textCapitalization: TextCapitalization.words,
                      autofillHints: const [AutofillHints.name],
                      decoration: const InputDecoration(
                        labelText: 'Nama lengkap',
                      ),
                      validator: (v) =>
                          (v ?? '').trim().isEmpty ? 'Nama wajib diisi' : null,
                    ),
                    const SizedBox(height: 18),
                  ],
                  TextFormField(
                    controller: email,
                    keyboardType: TextInputType.emailAddress,
                    autofillHints: const [AutofillHints.email],
                    decoration: const InputDecoration(labelText: 'Email'),
                    validator: (v) =>
                        RegExp(r'^[^@\s]+@[^@\s]+\.[^@\s]+$').hasMatch(v ?? '')
                        ? null
                        : 'Masukkan email yang valid',
                  ),
                  const SizedBox(height: 18),
                  TextFormField(
                    controller: password,
                    obscureText: hide,
                    autofillHints: [
                      register
                          ? AutofillHints.newPassword
                          : AutofillHints.password,
                    ],
                    decoration: InputDecoration(
                      labelText: 'Kata sandi',
                      helperText: register
                          ? 'Minimal 8 karakter, huruf dan angka'
                          : null,
                      suffixIcon: IconButton(
                        tooltip: hide
                            ? 'Tampilkan kata sandi'
                            : 'Sembunyikan kata sandi',
                        onPressed: () => setState(() => hide = !hide),
                        icon: Icon(
                          hide
                              ? Icons.visibility_outlined
                              : Icons.visibility_off_outlined,
                        ),
                      ),
                    ),
                    validator: (v) => (v ?? '').isEmpty
                        ? 'Kata sandi wajib diisi'
                        : register &&
                              ((v!.length < 8) ||
                                  !RegExp('[a-zA-Z]').hasMatch(v) ||
                                  !RegExp('[0-9]').hasMatch(v))
                        ? 'Gunakan minimal 8 karakter, huruf dan angka'
                        : null,
                  ),
                  if (register) ...[
                    const SizedBox(height: 18),
                    TextFormField(
                      controller: confirmation,
                      obscureText: true,
                      decoration: const InputDecoration(
                        labelText: 'Konfirmasi kata sandi',
                      ),
                      validator: (v) =>
                          v != password.text ? 'Kata sandi tidak cocok' : null,
                    ),
                  ],
                  const SizedBox(height: 26),
                  FilledButton(
                    onPressed: busy ? null : submit,
                    child: Text(
                      busy
                          ? 'Mohon tunggu...'
                          : register
                          ? 'Buat akun'
                          : 'Masuk',
                    ),
                  ),
                  const SizedBox(height: 18),
                  TextButton(
                    onPressed: busy
                        ? null
                        : () => setState(() {
                            register = !register;
                            error = null;
                            form.currentState?.reset();
                          }),
                    child: Text(
                      register
                          ? 'Sudah punya akun? Masuk'
                          : 'Belum punya akun? Daftar',
                    ),
                  ),
                  const SizedBox(height: 30),
                  const Text(
                    'Lingkungan bersih dimulai dari kita.',
                    textAlign: TextAlign.center,
                    style: TextStyle(fontSize: 12, color: muted),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    ),
  );
}
