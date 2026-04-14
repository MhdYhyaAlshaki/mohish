import 'package:bloc_test/bloc_test.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';
import 'package:mohish/core/data/api_client.dart';
import 'package:mohish/features/auth/data/auth_repository.dart';
import 'package:mohish/features/auth/domain/auth_user.dart';
import 'package:mohish/features/auth/presentation/auth_cubit.dart';

class _MockAuthRepository extends Mock implements AuthRepository {}

void main() {
  late _MockAuthRepository repository;

  setUp(() {
    repository = _MockAuthRepository();
  });

  blocTest<AuthCubit, AuthState>(
    'emits unauthenticated when bootstrap has no token',
    build: () {
      when(() => repository.restoreToken()).thenAnswer((_) async => null);
      return AuthCubit(repository);
    },
    act: (cubit) => cubit.bootstrap(),
    expect: () => [
      const AuthState.loading(),
      const AuthState.unauthenticated(),
    ],
  );

  blocTest<AuthCubit, AuthState>(
    'emits authenticated after successful login',
    build: () {
      when(
        () => repository.login(
          email: any(named: 'email'),
          password: any(named: 'password'),
        ),
      ).thenAnswer(
        (_) async => AuthResult(
          token: 'token',
          user: const AuthUser(
            id: 1,
            name: 'Mohish',
            email: 'm@example.com',
            points: 0,
            referralCode: 'ABC12345',
          ),
        ),
      );
      return AuthCubit(repository);
    },
    act: (cubit) => cubit.login('m@example.com', 'secret123'),
    expect: () => [
      const AuthState.loading(),
      const AuthState.authenticated(
        AuthUser(
          id: 1,
          name: 'Mohish',
          email: 'm@example.com',
          points: 0,
          referralCode: 'ABC12345',
        ),
      ),
    ],
  );

  blocTest<AuthCubit, AuthState>(
    'returns error state when login fails',
    build: () {
      when(
        () => repository.login(
          email: any(named: 'email'),
          password: any(named: 'password'),
        ),
      ).thenThrow(
        const ApiException(
          code: 'invalid_credentials',
          message: 'Invalid email or password.',
        ),
      );
      return AuthCubit(repository);
    },
    act: (cubit) => cubit.login('m@example.com', 'wrong'),
    verify: (cubit) {
      expect(cubit.state.status, AuthStatus.unauthenticated);
      expect(cubit.state.errorMessage, 'Invalid email or password.');
    },
  );
}
