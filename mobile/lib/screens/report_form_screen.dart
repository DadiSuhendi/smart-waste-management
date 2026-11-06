import 'dart:io';

import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import 'package:image_picker/image_picker.dart';

import '../core/api_client.dart';
import '../core/models.dart';
import '../widgets/common.dart';

class ReportFormScreen extends StatefulWidget {
  final ApiClient api;
  const ReportFormScreen({required this.api, super.key});
  @override
  State<ReportFormScreen> createState() => _ReportFormScreenState();
}

class _ReportFormScreenState extends State<ReportFormScreen> {
  final form = GlobalKey<FormState>();
  final title = TextEditingController(),
      description = TextEditingController(),
      address = TextEditingController(),
      latitude = TextEditingController(),
      longitude = TextEditingController();
  final picker = ImagePicker();
  XFile? photo;
  String? category, error, detection;
  bool busy = false, locating = false, detecting = false;
  String locationNote =
      'Gunakan GPS di lokasi sampah, atau isi koordinat manual.';
  @override
  void initState() {
    super.initState();
    recoverPhoto();
  }

  Future<void> recoverPhoto() async {
    if (!Platform.isAndroid) return;
    try {
      final lost = await picker.retrieveLostData();
      if (mounted && lost.files?.isNotEmpty == true) {
        setState(() => photo = lost.files!.first);
      }
    } catch (_) {
      if (mounted) {
        setState(
          () => error =
              'Foto sebelumnya tidak dapat dipulihkan. Silakan pilih ulang.',
        );
      }
    }
  }

  @override
  void dispose() {
    for (final c in [title, description, address, latitude, longitude]) {
      c.dispose();
    }
    super.dispose();
  }

  Future<void> pick(ImageSource source) async {
    try {
      final result = await picker.pickImage(
        source: source,
        maxWidth: 1800,
        imageQuality: 88,
        requestFullMetadata: false,
      );
      if (result == null || !mounted) return;
      if (await result.length() > 5 * 1024 * 1024) {
        throw const ApiException('Ukuran foto maksimal 5 MB.');
      }
      if (mounted) {
        setState(() {
          photo = result;
          detection = null;
          error = null;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(
          () =>
              error = 'Foto tidak dapat dibuka. Periksa izin kamera/galeri. $e',
        );
      }
    }
  }

  Future<void> locate() async {
    setState(() => locating = true);
    try {
      if (!await Geolocator.isLocationServiceEnabled()) {
        throw const ApiException(
          'Aktifkan GPS atau isi koordinat secara manual.',
        );
      }
      var permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
      }
      if (permission == LocationPermission.denied ||
          permission == LocationPermission.deniedForever) {
        throw const ApiException(
          'Izin lokasi tidak diberikan. Ubah izin di pengaturan perangkat atau isi koordinat manual.',
        );
      }
      final position = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.high,
          timeLimit: Duration(seconds: 20),
        ),
      );
      if (mounted) {
        setState(() {
          latitude.text = position.latitude.toStringAsFixed(7);
          longitude.text = position.longitude.toStringAsFixed(7);
          locationNote =
              'Lokasi diperoleh, perkiraan akurasi ${position.accuracy.round()} meter. Pastikan sesuai lokasi sampah.';
        });
      }
    } catch (e) {
      if (mounted) setState(() => locationNote = e.toString());
    } finally {
      if (mounted) setState(() => locating = false);
    }
  }

  Future<void> detect() async {
    final selected = photo;
    if (selected == null) return;
    setState(() {
      detecting = true;
      detection = 'Memeriksa foto...';
    });
    try {
      final result =
          (await widget.api.upload('/predictions', selected, {}))['data']
              as Map<String, dynamic>;
      if (mounted && photo == selected) {
        setState(() {
          category = result['category'] as String;
          detection =
              'Saran: ${categoryLabels[category] ?? category!} · ${((result['confidence'] as num) * 100).toStringAsFixed(1)}%. ${result['needs_review'] == true ? 'Keyakinan rendah, periksa kembali.' : 'Anda dapat mengubah kategori.'}';
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() => detection = '$e Pilih kategori secara manual.');
      }
    } finally {
      if (mounted) setState(() => detecting = false);
    }
  }

  Future<void> submit() async {
    if (!form.currentState!.validate()) return;
    if (photo == null) {
      setState(() => error = 'Tambahkan foto sampah terlebih dahulu.');
      return;
    }
    setState(() {
      busy = true;
      error = null;
    });
    try {
      await widget.api.upload('/reports', photo!, {
        'title': title.text.trim(),
        'description': description.text.trim(),
        'category': category!,
        'address': address.text.trim(),
        'latitude': latitude.text,
        'longitude': longitude.text,
      });
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Laporan berhasil dikirim. Terima kasih!'),
          ),
        );
        Navigator.pop(context, true);
      }
    } catch (e) {
      if (mounted) setState(() => error = e.toString());
    } finally {
      if (mounted) setState(() => busy = false);
    }
  }

  String? coordinate(String? value, double minimum, double maximum) {
    final number = double.tryParse(value ?? '');
    return number == null ||
            !number.isFinite ||
            number < minimum ||
            number > maximum
        ? 'Koordinat tidak valid'
        : null;
  }

  @override
  Widget build(BuildContext context) => PopScope(
    canPop: !busy,
    child: Scaffold(
      appBar: AppBar(
        title: const Text(
          'Buat laporan',
          style: TextStyle(fontSize: 19, fontWeight: FontWeight.w600),
        ),
      ),
      body: SafeArea(
        child: Center(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 680),
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(22),
              child: Form(
                key: form,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    const Text(
                      'Ada sampah di sekitar Anda?',
                      style: TextStyle(
                        fontSize: 23,
                        fontWeight: FontWeight.w600,
                        letterSpacing: -.5,
                      ),
                    ),
                    const SizedBox(height: 8),
                    const Text(
                      'Tambahkan foto dan lokasi. Kami bantu tindak lanjuti.',
                      style: TextStyle(color: muted, height: 1.6),
                    ),
                    const SizedBox(height: 24),
                    if (error != null) ErrorNotice(error!),
                    section('01', 'Foto sampah'),
                    const SizedBox(height: 12),
                    if (photo != null)
                      ClipRRect(
                        borderRadius: BorderRadius.circular(12),
                        child: Image.file(
                          File(photo!.path),
                          height: 200,
                          width: double.infinity,
                          fit: BoxFit.cover,
                        ),
                      )
                    else
                      Container(
                        height: 190,
                        decoration: BoxDecoration(
                          color: green,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: green),
                        ),
                        child: const Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(
                              Icons.document_scanner_outlined,
                              size: 44,
                              color: lime,
                            ),
                            SizedBox(height: 12),
                            Text(
                              'Foto yang jelas membantu penanganan',
                              style: TextStyle(fontSize: 12, color: paper),
                            ),
                          ],
                        ),
                      ),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        Expanded(
                          child: OutlinedButton.icon(
                            onPressed: busy || detecting
                                ? null
                                : () => pick(ImageSource.camera),
                            icon: const Icon(
                              Icons.camera_alt_outlined,
                              size: 18,
                            ),
                            label: const Text('Kamera'),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: OutlinedButton.icon(
                            onPressed: busy || detecting
                                ? null
                                : () => pick(ImageSource.gallery),
                            icon: const Icon(Icons.photo_outlined, size: 18),
                            label: const Text('Galeri'),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 10),
                    OutlinedButton.icon(
                      onPressed: photo == null || detecting || busy
                          ? null
                          : detect,
                      icon: const Icon(
                        Icons.document_scanner_outlined,
                        size: 18,
                      ),
                      label: Text(
                        detecting ? 'Memeriksa...' : 'Deteksi kategori foto',
                      ),
                    ),
                    if (detection != null)
                      Padding(
                        padding: const EdgeInsets.only(top: 10),
                        child: Text(
                          detection!,
                          style: const TextStyle(
                            color: muted,
                            fontSize: 12,
                            height: 1.6,
                          ),
                        ),
                      ),
                    const SizedBox(height: 28),
                    section('02', 'Detail laporan'),
                    const SizedBox(height: 18),
                    TextFormField(
                      controller: title,
                      maxLength: 150,
                      decoration: const InputDecoration(
                        labelText: 'Judul laporan',
                        hintText: 'Contoh: Sampah plastik di taman',
                      ),
                      validator: (v) =>
                          (v ?? '').trim().isEmpty ? 'Judul wajib diisi' : null,
                    ),
                    const SizedBox(height: 14),
                    TextFormField(
                      controller: description,
                      maxLines: 4,
                      maxLength: 5000,
                      decoration: const InputDecoration(
                        labelText: 'Deskripsi',
                        alignLabelWithHint: true,
                      ),
                      validator: (v) => (v ?? '').trim().length < 10
                          ? 'Jelaskan kondisi sampah minimal 10 karakter'
                          : null,
                    ),
                    const SizedBox(height: 14),
                    DropdownButtonFormField<String>(
                      key: ValueKey(category),
                      initialValue: category,
                      decoration: const InputDecoration(
                        labelText: 'Kategori sampah',
                      ),
                      items: categoryLabels.entries
                          .map(
                            (e) => DropdownMenuItem(
                              value: e.key,
                              child: Text(e.value),
                            ),
                          )
                          .toList(),
                      onChanged: busy
                          ? null
                          : (v) => setState(() => category = v),
                      validator: (v) => v == null ? 'Pilih kategori' : null,
                    ),
                    const SizedBox(height: 28),
                    section('03', 'Lokasi sampah'),
                    const SizedBox(height: 18),
                    TextFormField(
                      controller: address,
                      maxLength: 255,
                      decoration: const InputDecoration(
                        labelText: 'Alamat atau patokan',
                      ),
                      validator: (v) => (v ?? '').trim().isEmpty
                          ? 'Alamat wajib diisi'
                          : null,
                    ),
                    const SizedBox(height: 10),
                    OutlinedButton.icon(
                      onPressed: locating || busy ? null : locate,
                      icon: const Icon(Icons.my_location, size: 18),
                      label: Text(
                        locating ? 'Mencari lokasi...' : 'Gunakan lokasi saya',
                      ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      locationNote,
                      style: const TextStyle(
                        fontSize: 12,
                        color: muted,
                        height: 1.6,
                      ),
                    ),
                    const SizedBox(height: 18),
                    TextFormField(
                      controller: latitude,
                      keyboardType: const TextInputType.numberWithOptions(
                        decimal: true,
                        signed: true,
                      ),
                      decoration: const InputDecoration(labelText: 'Latitude'),
                      validator: (v) => coordinate(v, -90, 90),
                    ),
                    const SizedBox(height: 16),
                    TextFormField(
                      controller: longitude,
                      keyboardType: const TextInputType.numberWithOptions(
                        decimal: true,
                        signed: true,
                      ),
                      decoration: const InputDecoration(labelText: 'Longitude'),
                      validator: (v) => coordinate(v, -180, 180),
                    ),
                    const SizedBox(height: 28),
                    FilledButton.icon(
                      onPressed: busy || detecting ? null : submit,
                      icon: const Icon(Icons.arrow_forward, size: 18),
                      label: Text(
                        busy ? 'Mengirim laporan...' : 'Kirim laporan',
                      ),
                    ),
                    const SizedBox(height: 20),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    ),
  );
  Widget section(String number, String title) => Row(
    children: [
      Container(
        padding: const EdgeInsets.all(9),
        decoration: BoxDecoration(
          color: const Color(0xffe7ebd6),
          borderRadius: BorderRadius.circular(6),
        ),
        child: Text(number, style: const TextStyle(fontSize: 12, color: green)),
      ),
      const SizedBox(width: 12),
      Text(
        title,
        style: const TextStyle(fontSize: 17, fontWeight: FontWeight.w600),
      ),
    ],
  );
}
