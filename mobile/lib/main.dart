import 'package:flutter/material.dart';

import 'core/session.dart';
import 'screens/auth_screen.dart';
import 'screens/home_screen.dart';
import 'widgets/common.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  final session = Session();
  runApp(BersihApp(session: session));
  session.restore();
}

class BersihApp extends StatelessWidget {
  final Session session;
  const BersihApp({required this.session, super.key});
  @override
  Widget build(BuildContext context) => AnimatedBuilder(
    animation: session,
    builder: (context, child) => MaterialApp(
      key: ValueKey(session.user?.id),
      debugShowCheckedModeBanner: false,
      title: 'Bersih',
      theme: ThemeData(
        useMaterial3: true,
        scaffoldBackgroundColor: canvas,
        colorScheme: ColorScheme.fromSeed(
          seedColor: green,
          primary: green,
          surface: paper,
        ),
        appBarTheme: const AppBarTheme(
          backgroundColor: canvas,
          surfaceTintColor: Colors.transparent,
          centerTitle: false,
        ),
        cardTheme: CardThemeData(
          elevation: 0,
          color: paper,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(14),
            side: const BorderSide(color: Color(0xffe3e4d9)),
          ),
        ),
        inputDecorationTheme: InputDecorationTheme(
          filled: true,
          fillColor: paper,
          contentPadding: const EdgeInsets.symmetric(
            horizontal: 16,
            vertical: 16,
          ),
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(9),
            borderSide: const BorderSide(color: Color(0xffd7dccf)),
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(9),
            borderSide: const BorderSide(color: Color(0xffd7dccf)),
          ),
        ),
        filledButtonTheme: FilledButtonThemeData(
          style: FilledButton.styleFrom(
            minimumSize: const Size(0, 48),
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(8),
            ),
          ),
        ),
        outlinedButtonTheme: OutlinedButtonThemeData(
          style: OutlinedButton.styleFrom(
            minimumSize: const Size(0, 46),
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(8),
            ),
          ),
        ),
        textTheme: const TextTheme(
          bodyMedium: TextStyle(color: Color(0xff304537)),
          bodyLarge: TextStyle(color: Color(0xff304537)),
        ),
      ),
      home: session.loading
          ? const Scaffold(body: Center(child: CircularProgressIndicator()))
          : session.error != null
          ? Scaffold(
              body: SafeArea(
                child: Center(
                  child: Padding(
                    padding: const EdgeInsets.all(24),
                    child: ErrorNotice(session.error!, retry: session.restore),
                  ),
                ),
              ),
            )
          : session.user == null
          ? AuthScreen(session: session)
          : HomeScreen(session: session),
    ),
  );
}
