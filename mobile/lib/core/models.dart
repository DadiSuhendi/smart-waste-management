class UserProfile {
  final int id;
  final String name, email, role;
  const UserProfile({
    required this.id,
    required this.name,
    required this.email,
    required this.role,
  });
  factory UserProfile.fromJson(Map<String, dynamic> json) => UserProfile(
    id: json['id'] as int,
    name: json['name'] as String,
    email: json['email'] as String,
    role: json['role'] as String,
  );
}

const statusLabels = {
  'pending': 'Menunggu',
  'in_progress': 'Diproses',
  'completed': 'Selesai',
  'rejected': 'Ditolak',
};
const categoryLabels = {
  'organic': 'Organik',
  'plastic': 'Plastik',
  'paper': 'Kertas',
};

class WasteReport {
  final int id;
  final String reference,
      title,
      description,
      category,
      status,
      address,
      photoUrl;
  final double latitude, longitude;
  final DateTime createdAt;
  final String? aiCategory;
  final double? aiConfidence;
  final List<Map<String, dynamic>> histories;
  WasteReport.fromJson(Map<String, dynamic> j)
    : id = j['id'] as int,
      reference = j['reference'] as String,
      title = j['title'] as String,
      description = j['description'] as String,
      category = j['category'] as String,
      status = j['status'] as String,
      address = j['address'] as String,
      photoUrl = j['photo_url'] as String,
      latitude = (j['latitude'] as num).toDouble(),
      longitude = (j['longitude'] as num).toDouble(),
      createdAt = DateTime.parse(j['created_at'] as String).toLocal(),
      aiCategory = j['ai_category'] as String?,
      aiConfidence = (j['ai_confidence'] as num?)?.toDouble(),
      histories = (j['histories'] as List? ?? [])
          .map((e) => Map<String, dynamic>.from(e as Map))
          .toList();
}

class ReportPage {
  final List<WasteReport> reports;
  final int page, lastPage, total;
  ReportPage.fromJson(Map<String, dynamic> json)
    : reports = (json['data'] as List)
          .map((j) => WasteReport.fromJson(j as Map<String, dynamic>))
          .toList(),
      page = json['meta']['current_page'] as int,
      lastPage = json['meta']['last_page'] as int,
      total = json['meta']['total'] as int;
}

String displayDate(DateTime date) =>
    '${date.day.toString().padLeft(2, '0')}/${date.month.toString().padLeft(2, '0')}/${date.year}';
