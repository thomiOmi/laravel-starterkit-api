# Changelog

All notable changes to this project will be documented in this file.

## [0.19.1](https://github.com/thomiOmi/laravel-starterkit-api/releases/tag/v0.19.1) (2026-07-10)

### Performance Improvements

- **iam:** optimize CreateUserAction and UpdateUserAction with eager loading ([4c5da5e](https://github.com/thomiOmi/laravel-starterkit-api/commit/4c5da5e497d9f3c11a2cab1eb6dcebbd83bf91b1))

### Continuous Integration

- **release:** drop redundant git config (env vars handle authorship) ([0433945](https://github.com/thomiOmi/laravel-starterkit-api/commit/043394583c02b2e03ef70ffabe27282363ce931b))
- **release:** set git author to triggering actor ([686f83c](https://github.com/thomiOmi/laravel-starterkit-api/commit/686f83c08fe6dbd6a80cd0f685f857ecb9ba0581))
## [0.19.0](https://github.com/thomiOmi/laravel-starterkit-api/releases/tag/v0.19.0) (2026-07-09)

### Features

- **iam:** add Spatie permission middleware to role and permission routes ([3c215fd](https://github.com/thomiOmi/laravel-starterkit-api/commit/3c215fd66cb8ee10f919b1f48d5b2bb99583bda6))
- **iam:** add description to PermissionFactory, migrate to ULID-based Spatie permissions ([869e13d](https://github.com/thomiOmi/laravel-starterkit-api/commit/869e13d440c93303a9713210aaa834f9d7786604))
- add token expiration (expires_at/expires_in) to auth response ([41a472f](https://github.com/thomiOmi/laravel-starterkit-api/commit/41a472f367541e8e3c65fdb711bb94cc396d31d2))
- **skills:** add modular-architecture skill and update project guidelines ([6b5a537](https://github.com/thomiOmi/laravel-starterkit-api/commit/6b5a53709da35309650a7fb5cd436adeae5c4e2f))
- Enhance model behavior and documentation support ([679f389](https://github.com/thomiOmi/laravel-starterkit-api/commit/679f389015390709051ba90f06061a80c5ed8374))
- **config:** create architecture.php constitution ([6aca0fd](https://github.com/thomiOmi/laravel-starterkit-api/commit/6aca0fd39d514dd6ea4d7c7a7b6485e0fccbb029))
- add laravel-attributes skill documentation and examples ([675ffaa](https://github.com/thomiOmi/laravel-starterkit-api/commit/675ffaa1f995ba548ca2c19d8f6c2b1fdd5a6c87))
- **auth:** add device_name override to register + fix test quoting ([971e0f1](https://github.com/thomiOmi/laravel-starterkit-api/commit/971e0f172071f4dace838e7eff33ef3036d0d3dd))
- **auth:** implement Sanctum hybrid auth (token + session) ([f0d6828](https://github.com/thomiOmi/laravel-starterkit-api/commit/f0d68284f3a6a92fefece2f4f99e36a800280bc5))
- **filters:** sparse fieldsets, PHP 8 attributes, locale auto-detect, and config docs ([63e0594](https://github.com/thomiOmi/laravel-starterkit-api/commit/63e0594b3a3617ab6ddcebbff71cbb955bda8c28))
- **traits:** make passwordRules confirmed param optional for auth module ([f54e4bb](https://github.com/thomiOmi/laravel-starterkit-api/commit/f54e4bb23c418f1c04a594b2031cfe6868720371))
- **auth, role, user:** auth events, social login, role assignment & fixes ([819dc09](https://github.com/thomiOmi/laravel-starterkit-api/commit/819dc09f830e2537669d6cd1780921865bf31185))
- **auth:** email verification middleware, socialite denial handling, verified routes ([0db852e](https://github.com/thomiOmi/laravel-starterkit-api/commit/0db852e24314bcbba12ba0cdb43b7b21e4fb840d))
- **auth:** improve auth module with contracts, events, abilities & prune schedule ([6f6b1ce](https://github.com/thomiOmi/laravel-starterkit-api/commit/6f6b1ceca4a7dd26858e8acc78fbcae79aa9f5a4))
- adapt forked cloud skills to codebase conventions ([f9cb3ac](https://github.com/thomiOmi/laravel-starterkit-api/commit/f9cb3aca46677c1e3ba761fb32536932feda5eca))
- add Jules agent workflows (TURBO, SENTINEL, CUSTODIAN) and documentation ([8a64c7f](https://github.com/thomiOmi/laravel-starterkit-api/commit/8a64c7f40433d5ff663e059faff26a5ada5edcfe))
- Add workflow_dispatch event to Laravel workflow ([58dad36](https://github.com/thomiOmi/laravel-starterkit-api/commit/58dad36c720ac4ce1546dedf3dc5336927e66c02))
- **api:** add Scramble extension for SuccessResponse property schemas ([440dc74](https://github.com/thomiOmi/laravel-starterkit-api/commit/440dc74689921c955e24e64336d7f87e2aaf31cd))
- FormRequest strict mode and filter query parameter docs ([08e6d80](https://github.com/thomiOmi/laravel-starterkit-api/commit/08e6d809b9fb6a112d74dfecb37ce49002b39a0f))
- rename messages.php to general.php, add avatar field for Socialite ([15e85a3](https://github.com/thomiOmi/laravel-starterkit-api/commit/15e85a3702c1f5d4093462bdc85d08a7ecb9a61c))
- move auth controllers from User module to Auth module ([a0ced58](https://github.com/thomiOmi/laravel-starterkit-api/commit/a0ced58d0a3ffd569d9eb6275ae593eac267f2e1))
- complete all modules with device management, social login, and permission CRUD ([40a7fc2](https://github.com/thomiOmi/laravel-starterkit-api/commit/40a7fc2405ce54630f87c96a239096eec691741e))
- **console:** update module generator to align with Golden Standard ([ae271c6](https://github.com/thomiOmi/laravel-starterkit-api/commit/ae271c63a4b341b59d0bb6ec80e0dcc577817f81))

### Bug Fixes

- **guidelines:** remove JSON output options from PHPStan and Pest commands ([934009c](https://github.com/thomiOmi/laravel-starterkit-api/commit/934009c8a35b13b89fb5de0809a86a0d26fc2bc4))
- **i.am:** standardize Spatie permission guard to sanctum for Sanctum-only API ([c3c366b](https://github.com/thomiOmi/laravel-starterkit-api/commit/c3c366b8d9257815679c1a3471db164205bac6ef))
- suppress NoDiscard in BulkDeleteUsersAction cache test ([9bdae89](https://github.com/thomiOmi/laravel-starterkit-api/commit/9bdae8911e0bcefad007d12aa142ceb36196f6dd))
- suppress NoDiscard on test calls that ignore return values ([bb5225f](https://github.com/thomiOmi/laravel-starterkit-api/commit/bb5225fc7def27f092cdc8dbc6fc7aaa675f8211))
- **phpstan:** resolve 160 errors at level max and fix arch test violation ([da510aa](https://github.com/thomiOmi/laravel-starterkit-api/commit/da510aa66cbd94c06451744bcb7ec331981dd73a))
- **n+1:** eager-load relationships in ShowUserAction and ShowRoleAction ([db1b087](https://github.com/thomiOmi/laravel-starterkit-api/commit/db1b08747360d37cc2ce8e8bee7ab7948ef546e6))
- reorder ForgotPasswordController import for consistency ([3842276](https://github.com/thomiOmi/laravel-starterkit-api/commit/38422762851c16239db6da4fa1cb4f73ec4d97bb))
- isolate Spatie permission cache per parallel worker to prevent race conditions ([368401e](https://github.com/thomiOmi/laravel-starterkit-api/commit/368401e185a0fceca02eeae108046d677d0b15d9))
- **CI:** Modify CI workflow permissions for write access ([8984904](https://github.com/thomiOmi/laravel-starterkit-api/commit/8984904a5da251eb7d69c5bea5833d28667827b5))
- harden strict typing and resolve PHPStan 2.0 errors ([f1017d9](https://github.com/thomiOmi/laravel-starterkit-api/commit/f1017d924f5ba7ff92daa38073f8574ea046d35a))
- resolve PHPStan 2.0 list type and callable errors in IAM module ([0998878](https://github.com/thomiOmi/laravel-starterkit-api/commit/0998878114b2fbfb8e2890bc7684087ca976b1dd))
- resolve strict typing issues in IAM resources and controllers ([f5697b6](https://github.com/thomiOmi/laravel-starterkit-api/commit/f5697b692e7466f7b245078f8ea1c0ec50b703af))
- harden type inference in IAM resources and controllers ([ff24000](https://github.com/thomiOmi/laravel-starterkit-api/commit/ff240008b7ce1fc7df62cadee2031ce9bab68d4d))
- resolve specific type mismatches and generic return types ([e63bf8b](https://github.com/thomiOmi/laravel-starterkit-api/commit/e63bf8b9182b58874e526b8ac4d8ecb9bc30f560))
- address PHPStan generic and relationship type errors in IAM module ([ed1150a](https://github.com/thomiOmi/laravel-starterkit-api/commit/ed1150abd7e14c91d0f49c0900efe12486a8efdb))
- resolve type mismatches in IAM resources and requests ([4f45b9c](https://github.com/thomiOmi/laravel-starterkit-api/commit/4f45b9cbeb2c8676ea45845ff5c5557f6a00f9f0))
- **CI:** Rename workflow from 'Laravel' to 'Testing Laravel' ([5582b3c](https://github.com/thomiOmi/laravel-starterkit-api/commit/5582b3c508dee39db3c35c2d78eb550bee4a791e))
- **tests:** use firstOrCreate instead of create to prevent PermissionAlreadyExists in CI ([71c6b93](https://github.com/thomiOmi/laravel-starterkit-api/commit/71c6b939e500cd281d436870ac2d74163050026c))
- **tests:** resolve 3 pre-existing test failures ([f5095aa](https://github.com/thomiOmi/laravel-starterkit-api/commit/f5095aa8f10a9fcccf1c6b495000441503f6cf46))
- **CI:** Potential fix for code scanning alert no. 2: Workflow does not contain permissions ([4c8d312](https://github.com/thomiOmi/laravel-starterkit-api/commit/4c8d312b5bd873cd2e99f99a25cc421e41b99fc5))
- **auth:** Load missing roles and permissions when returning user data in RegisterAction and SocialCallbackAction ([7ee9d79](https://github.com/thomiOmi/laravel-starterkit-api/commit/7ee9d79c697446cbb16c7c9c6bbcba5dac14ba76))
- **auth:** replace fake() helper with static fallback in SocialCallbackAction ([8c50b87](https://github.com/thomiOmi/laravel-starterkit-api/commit/8c50b87f7d736bd66d5d7b168610f614a40fdc03))
- **cache:** allow unserializing Eloquent models from cache ([0e214bc](https://github.com/thomiOmi/laravel-starterkit-api/commit/0e214bc1f16483ee8b394dc15a109bee5808129e))
- **errors:** add default fallback for problem type slug resolution ([0768c72](https://github.com/thomiOmi/laravel-starterkit-api/commit/0768c7255c4cc10c17aafcb0adf8e178715f4ce9))
- middleware priority — EnsureFrontendRequestsAreStateful before Authenticate ([f4f48ef](https://github.com/thomiOmi/laravel-starterkit-api/commit/f4f48efdc31b1a15f71c7c41433d7de3370d87e4))
- MySQL quoting in BaseFilterTest and increase CI rate limit ([4a60b8d](https://github.com/thomiOmi/laravel-starterkit-api/commit/4a60b8d2f634822950af46dc837e8b803eb2ce28))
- set APP_TIMEZONE default to UTC in .env.example ([a828734](https://github.com/thomiOmi/laravel-starterkit-api/commit/a828734f76a80c82fbc13e538ca7ef81b0f22ae5))
- **auth:** correct ResetPasswordAction error message for non-string status ([93b7ee3](https://github.com/thomiOmi/laravel-starterkit-api/commit/93b7ee3a0c15ba69fd760dec983feba33ead2e82))
- restore phpstan config include, cast env() calls in config files ([91e4d9c](https://github.com/thomiOmi/laravel-starterkit-api/commit/91e4d9c5ace5ba009d46ab6949d88228b8d32c96))
- broken auth validation, role seed in test, architecture test, phpstan config types ([c544ad8](https://github.com/thomiOmi/laravel-starterkit-api/commit/c544ad8e60b9f136c381654aada82d9fe5f4dbe1))
- **auth:** update login error message and add password field ([afe9dad](https://github.com/thomiOmi/laravel-starterkit-api/commit/afe9dad417888a97a8ab96776fc4023bb90d4fe1))
- reload permissions relation after syncPermissions to return fresh data ([981ca24](https://github.com/thomiOmi/laravel-starterkit-api/commit/981ca2461365997de8c9527d5746ae82275655db))
- resolve SENTINEL security findings and pre-existing arch test failure ([785eab1](https://github.com/thomiOmi/laravel-starterkit-api/commit/785eab12da9cf8a07f0d024d8cd132cbbaa450b2))
- resolve PHPStan factory() type error in UserSeeder ([da6a4e1](https://github.com/thomiOmi/laravel-starterkit-api/commit/da6a4e13dba0d512a5fb15c77ccc289f072b34d2))
- resolve CI failures and refactor modules to Golden Standard ([fb5bce3](https://github.com/thomiOmi/laravel-starterkit-api/commit/fb5bce38da863a7fc6be42f3a4bfec8cdb61ffdc))
- robust API response handling and locale detection ([11f556a](https://github.com/thomiOmi/laravel-starterkit-api/commit/11f556a86420efcaf9e5d5201bbddf60a53ed73b))
- resolve CI failure by hardening SuccessResponse and bootstrap/app.php ([8bc846b](https://github.com/thomiOmi/laravel-starterkit-api/commit/8bc846b072f061f344095052ba356223b7de74dc))
- **api:** correct delete controller return types to include ProblemResponse ([2bc9421](https://github.com/thomiOmi/laravel-starterkit-api/commit/2bc94216948807d38f7121900d04464958e12bde))
- remove model caching in findById, fix BulkActionRequest auth, clean up route names ([5ab2dc2](https://github.com/thomiOmi/laravel-starterkit-api/commit/5ab2dc2edf052b1bd63695138683e771525e1ce4))
- ci failure by pinning php 8.4.1 and sync ai/stubs with architecture ([b7b1b9c](https://github.com/thomiOmi/laravel-starterkit-api/commit/b7b1b9c10445a2ddc256ea6b629b333886286b91))
- ci failure by pinning php 8.4.1 and sync ai/stubs with architecture ([42fafda](https://github.com/thomiOmi/laravel-starterkit-api/commit/42fafdac075f2fe456b1f4d65f53f63d441e3e2d))
- ci failure by pinning php 8.4.1 and sync stubs with latest architecture ([0c809a0](https://github.com/thomiOmi/laravel-starterkit-api/commit/0c809a0dd2c898e70c6add014f8242b07f1cec78))
- resolve InvalidSignatureException shadowing in bootstrap ([e67cf84](https://github.com/thomiOmi/laravel-starterkit-api/commit/e67cf8461a8e0f23c9421590c3e5ad335d797ad7))
- add ProblemResponse handler for InvalidSignatureException ([817d62e](https://github.com/thomiOmi/laravel-starterkit-api/commit/817d62e58cf0d62cecb413d4f354d1e5a49a1c5a))
- replace dead AuthorizationException/ModelNotFoundException handlers ([e2e3190](https://github.com/thomiOmi/laravel-starterkit-api/commit/e2e3190273404d05eb86a23d563511dce546b6c6))
- resolve CI failure by normalizing API route version discovery ([d566aa3](https://github.com/thomiOmi/laravel-starterkit-api/commit/d566aa3347818ca1d752f9718a8b8cec2414b132))
- resolve GitHub Action versioning and typo in workflow ([011cfff](https://github.com/thomiOmi/laravel-starterkit-api/commit/011cfff0c76e4880b179a0ecc6aaf422a5a27fc9))

### Performance Improvements

- **iam:** optimize ShowPermissionAction with sparse field selection ([639db3f](https://github.com/thomiOmi/laravel-starterkit-api/commit/639db3f8929bb39194b44fa4e6bc99007a3dd36a))
- optimize role and permission listing with eager loading and sparse fields ([cdd8f5e](https://github.com/thomiOmi/laravel-starterkit-api/commit/cdd8f5e76674bd6bce99eb2962fcc6230b0ac24d))
- optimize user listing with eager loading and sparse fields ([f36e319](https://github.com/thomiOmi/laravel-starterkit-api/commit/f36e31939add3cc6a543f18ffee0c9e5595b9af7))
- **auth:** optimize authentication actions with eager loading ([06ccee7](https://github.com/thomiOmi/laravel-starterkit-api/commit/06ccee7d5512ad92a7650f84342a93d185c26b82))
- **auth:** optimize login and social callback with eager loading ([5749aa8](https://github.com/thomiOmi/laravel-starterkit-api/commit/5749aa8279b037422a2ca461910b5a5a4c62736b))
- **user:** implement caching for findById with proper invalidation ([df007d8](https://github.com/thomiOmi/laravel-starterkit-api/commit/df007d8cce4c11b32fcf9d5b5d184151780fc164))
- **auth:** optimize ListDevicesAction by selecting specific columns ([26509a7](https://github.com/thomiOmi/laravel-starterkit-api/commit/26509a79ce13ac4db4bfdd5d7002ac8e62915044))
- optimize redundant database queries in Auth and CRUD modules ([3f29a12](https://github.com/thomiOmi/laravel-starterkit-api/commit/3f29a12cc6e74c79f8a931b009ccce4227cc2a00))
- optimize redundant database queries in Auth and CRUD modules ([c134702](https://github.com/thomiOmi/laravel-starterkit-api/commit/c134702431630bc552b6e97229a9cac55505eb10))
- hunt and fix performance bottlenecks in User and Auth modules ([4cd6a38](https://github.com/thomiOmi/laravel-starterkit-api/commit/4cd6a38f9702c92842ef08c9f1f1bc6f288814c2))

### Code Refactoring

- **iam:** use PermissionEnum and RoleEnum instead of string permission and role names ([44fc01e](https://github.com/thomiOmi/laravel-starterkit-api/commit/44fc01e8509cfd12d2567435e82c6152f19611a3))
- **iam:** remove custom HasSoftDeletes, drop soft delete for Role/Permission ([a62b5f2](https://github.com/thomiOmi/laravel-starterkit-api/commit/a62b5f298362d5441d5869bfa16e29e719c2c622))
- switch code coverage tool from PCOV to Xdebug in CI workflow ([d693fe9](https://github.com/thomiOmi/laravel-starterkit-api/commit/d693fe936d270e19e8f226b38f3469ea61aff4b3))
- update type coverage command in documentation for consistency ([c2b76cd](https://github.com/thomiOmi/laravel-starterkit-api/commit/c2b76cd6fce582efdcef3c795e9ebe330009f587))
- update CI workflow to include PCOV settings and adjust PHPUnit test suite structure ([85052d1](https://github.com/thomiOmi/laravel-starterkit-api/commit/85052d1c920e4157e97c9b1a1da8f618b2bc8659))
- update type coverage command in code quality rules across documentation ([f4aaf23](https://github.com/thomiOmi/laravel-starterkit-api/commit/f4aaf23e8c2310a87d9c079036f7e9384fc37839))
- update PHP setup in CI workflow to include additional extensions and coverage tool ([b251c70](https://github.com/thomiOmi/laravel-starterkit-api/commit/b251c7085fd5090749376d7328b22bdc6f6d2dd3))
- update ShowRoleAction and ShowUserAction to use select for specific fields ([cdb3f64](https://github.com/thomiOmi/laravel-starterkit-api/commit/cdb3f6469822ad08eedea24baff3a41be5e9111c))
- remove NoDiscard annotation from handle method in Actions documentation ([32784d5](https://github.com/thomiOmi/laravel-starterkit-api/commit/32784d564dae72d74af54e091feea09bf0e85b7b))
- remove NoDiscard attribute from Action handle methods in modular architecture ([869e71a](https://github.com/thomiOmi/laravel-starterkit-api/commit/869e71a2c0f1e702d30dcbf4a53b0245adcf7aa4))
- remove NoDiscard attribute from all Actions ([c46a71b](https://github.com/thomiOmi/laravel-starterkit-api/commit/c46a71b331e2d099689d1502a866f0481d14aa73))
- remove redundant casts() override in PersonalAccessToken ([47383fc](https://github.com/thomiOmi/laravel-starterkit-api/commit/47383fcaddf57dad01fc3e2916f68ee1856d5f55))
- consolidate codebase audit fixes ([34893a1](https://github.com/thomiOmi/laravel-starterkit-api/commit/34893a1f74108b27121c6829cc0619182f652536))
- Remove outdated reference documents for Laravel patterns, modern PHP features, PHP standards, property hooks, Symfony patterns, and testing quality assurance. Update boost.json to reflect the removal of these references. ([7f52254](https://github.com/thomiOmi/laravel-starterkit-api/commit/7f52254f729925a60bf29557a76c82ae7d14edee))
- **migrations:** consolidate schema and add query indexes ([271aaea](https://github.com/thomiOmi/laravel-starterkit-api/commit/271aaea78e4997b9afd742971395947a9f2c4890))
- remove static factory methods from ProblemResponse and SuccessResponse classes ([5aeea3d](https://github.com/thomiOmi/laravel-starterkit-api/commit/5aeea3d5fe6fe01ce507c9d0bcf56c9aeeb25c10))
- **controllers:** rename list controllers to {Resource}ListController ([199228d](https://github.com/thomiOmi/laravel-starterkit-api/commit/199228d90996fd5b5199f0277d84660b40eda217))
- **routes:** rename route names to short format without api prefix ([bd37901](https://github.com/thomiOmi/laravel-starterkit-api/commit/bd37901774bf2b79f8f32f61d96268b398382710))
- **core:** replace hardcoded type casts with PHPStan-friendly alternatives ([7aa8fec](https://github.com/thomiOmi/laravel-starterkit-api/commit/7aa8fec9f6d2f49dcbf8b37ad1922f6d55d7c8dd))
- **core:** standardize config access, response construction, and architecture layers ([963426f](https://github.com/thomiOmi/laravel-starterkit-api/commit/963426f8fad0f459c1b0291438a0982d65cd5547))
- **controllers:** consolidate response types, strip phantom @throws, remove dead null checks ([8188d0c](https://github.com/thomiOmi/laravel-starterkit-api/commit/8188d0c1989a86e3714a002ab5cdb05af649f8c1))
- **scramble:** replace ProblemResponseExtension with ExceptionToResponse-based handling ([59bac0b](https://github.com/thomiOmi/laravel-starterkit-api/commit/59bac0b14f3900867b219e44d7acb9ec763176fc))
- remove unused scramble configuration and enhance response handling in API controllers ([6d0c9a1](https://github.com/thomiOmi/laravel-starterkit-api/commit/6d0c9a1e29d49e8822c64edf6586f8305f0decd5))
- **i.am:** delete legacy Auth, User, Role modules and fix route names ([d7dff9f](https://github.com/thomiOmi/laravel-starterkit-api/commit/d7dff9fd942962a4ffb11b80f5e76a465dde1b1f))
- **i.am:** consolidate Auth, User, Role modules into IAM module ([f1601fc](https://github.com/thomiOmi/laravel-starterkit-api/commit/f1601fcc74a41804ee99ea2cecc10c6343b93e1f))
- **tests:** simplify comment descriptions in PasswordAndVerificationTest and IntegrationTest ([60dee83](https://github.com/thomiOmi/laravel-starterkit-api/commit/60dee83f5890f243d308f5c75b0d900ac521bf2c))
- **tests:** simplify test descriptions by removing SOP references ([0a18fe7](https://github.com/thomiOmi/laravel-starterkit-api/commit/0a18fe77433810ebd070e44777aa70c3c6f631de))
- **tests:** clean up test files and improve notification assertions ([1ff8166](https://github.com/thomiOmi/laravel-starterkit-api/commit/1ff816694fed7673d7dbf63ea6a4e5e9ede331a1))
- **unit-tests:** rescue and standardize modular unit tests ([c6a94f4](https://github.com/thomiOmi/laravel-starterkit-api/commit/c6a94f48aedd7c7006c09172a52d2e6f14fbebf5))
- **tests:** relocate infrastructure tests to root tests directory ([ca957de](https://github.com/thomiOmi/laravel-starterkit-api/commit/ca957de3dd5db6fe6374af4591f5ef4c6997bb00))
- **auth:** remove hybrid stateful/stateless auth logic ([135cbb1](https://github.com/thomiOmi/laravel-starterkit-api/commit/135cbb1582b805e026651d05925fa774155085b9))
- move all traits from app/Traits to app/Concerns ([149a713](https://github.com/thomiOmi/laravel-starterkit-api/commit/149a713fc71a0ecd1fbbac46e4a78063917d2f00))
- move FormatDates trait from app/Http/Resources/Concerns to app/Concerns ([80f4899](https://github.com/thomiOmi/laravel-starterkit-api/commit/80f4899a0111f369d8d6c8c886d2c506b67c56af))
- **resources:** extract formatDate into shared FormatDates trait ([a3d5f72](https://github.com/thomiOmi/laravel-starterkit-api/commit/a3d5f725acecb9638ef38f3a5ee5c23fe6514020))
- **trusted-hosts:** move to config/app.php, use parse_url for host extraction ([dd4f336](https://github.com/thomiOmi/laravel-starterkit-api/commit/dd4f3369a7bfc7847429a358690d2b26b0b444f5))
- **auth:** use Password facade instead of PasswordBroker DI ([24ff651](https://github.com/thomiOmi/laravel-starterkit-api/commit/24ff6515348e094a3d091e628b140d0e60dc5875))
- **auth:** simplify ForceJsonResponse, remove reflection hack and shouldRenderJsonWhen ([2eb60c0](https://github.com/thomiOmi/laravel-starterkit-api/commit/2eb60c0e21adddc9c5fcb484ff2566c76c400093))
- **auth:** fix PHPStan return types in rules() ([9ad41e0](https://github.com/thomiOmi/laravel-starterkit-api/commit/9ad41e0b1a3354a9f9637df3ac27cb26cff6f8f1))
- **auth:** standardize validation and fix style issues ([08e0ed6](https://github.com/thomiOmi/laravel-starterkit-api/commit/08e0ed6b897928c16d408b9021072c750e8d3701))
- **auth:** use validation traits and harden ID comparison ([1f22942](https://github.com/thomiOmi/laravel-starterkit-api/commit/1f2294238a988c84773992ba8cc4a42072a359a6))
- **controllers:** replace hardcoded status codes with Response constants ([533ed82](https://github.com/thomiOmi/laravel-starterkit-api/commit/533ed82d7c776b0efe79180b56cc5d3472e4800c))
- **validation:** extract shared validation rules into reusable traits ([151f592](https://github.com/thomiOmi/laravel-starterkit-api/commit/151f59246249d84fd92e51d00e7a949bdda3afc5))
- replace @return annotations with #[Response] attributes for OpenAPI docs ([4fa49c4](https://github.com/thomiOmi/laravel-starterkit-api/commit/4fa49c47ba034d930ff2f22119e5ea15da44c877))
- add ResourceCollection support to SuccessResponse and improve Scramble docs ([595fa9e](https://github.com/thomiOmi/laravel-starterkit-api/commit/595fa9e8c5b68503ad395eff1ee939f3f22f8670))
- **auth:** extract inline controllers to actions, add missing indexes and fixes ([7ef2017](https://github.com/thomiOmi/laravel-starterkit-api/commit/7ef20173f6df0b07de9e5d256a7e4f1488cb53a6))
- **api:** migrate to SuccessResponse/ProblemResponse envelope and remove BaseResource ([117b6f1](https://github.com/thomiOmi/laravel-starterkit-api/commit/117b6f11a002a9b0f00659c461205abdebd92838))
- **api:** simplify response layer and cleanup Scramble extensions ([c6bbba5](https://github.com/thomiOmi/laravel-starterkit-api/commit/c6bbba594ecfc11d91f8c7548aad194e3c1b8098))
- code review fixes - cleanup, alignment, and test coverage ([e85fc6d](https://github.com/thomiOmi/laravel-starterkit-api/commit/e85fc6d75d52c9658c3cef86b56af3743993566e))
- convert api-reference and architecture into Agent Skills ([bcb3c85](https://github.com/thomiOmi/laravel-starterkit-api/commit/bcb3c8533de7366a1030ce33e3854cbef3ba6e7f))
- configurable error type URL, remove dead code, fix missing translation ([cb103e1](https://github.com/thomiOmi/laravel-starterkit-api/commit/cb103e18549e2ccc5f535626a61d2cc21b97659f))
- implement audit feedback and cleanup ([4716ad5](https://github.com/thomiOmi/laravel-starterkit-api/commit/4716ad5e06029c3016384f89b69b1f32686946fb))
- comprehensive codebase hardening and documentation polish ([dccb794](https://github.com/thomiOmi/laravel-starterkit-api/commit/dccb7944a4b6bff92e3ca23ffa05a4c95c19575e))
- reduce filter duplication, cleanup imports, and harden bulk roles deletion ([b3b40e0](https://github.com/thomiOmi/laravel-starterkit-api/commit/b3b40e0b9911304d3f3000c86f32fb563ff0f6dd))
- remove DB::transaction wrappers, read-only repositories, inject Guard in actions ([6d693c8](https://github.com/thomiOmi/laravel-starterkit-api/commit/6d693c8ba8fcfeb3f9fb3c2d897c7e4bb59eec38))
- consolidate UserResource, fix Pint config, upgrade module generator stubs ([1c7ccfd](https://github.com/thomiOmi/laravel-starterkit-api/commit/1c7ccfd42f32df9ee905d920a33ac3644f2f434f))
- **role:** implement golden standard architecture for role module ([aa0772e](https://github.com/thomiOmi/laravel-starterkit-api/commit/aa0772e588c833d44648b34e5552c1b6b70f72ce))
- **auth:** align Auth module with Golden Standard architecture ([95a489e](https://github.com/thomiOmi/laravel-starterkit-api/commit/95a489e5b471b5348ab5ac588360f7c72a8503d7))
- **user:** implement golden standard architecture for user module ([4a00a98](https://github.com/thomiOmi/laravel-starterkit-api/commit/4a00a989e671688ab3f9811998fae75c9fdd9768))
- use PHP 8 attributes for Artisan command configuration ([056e398](https://github.com/thomiOmi/laravel-starterkit-api/commit/056e39843ffb0fa9f3bc9feb661fafe69b958e5b))
- rename JsonDataResponse to DataResponse for naming consistency ([0e2adb9](https://github.com/thomiOmi/laravel-starterkit-api/commit/0e2adb9ecc5b8bbef354c207ce3fae021d09e079))

### Documentation

- remove NoDiscard rule from skills ([ec1e1c5](https://github.com/thomiOmi/laravel-starterkit-api/commit/ec1e1c5e886d703e9bb40ae8878f0f55d8810fb4))
- fix response format, namespaces, structure in README and docs/ ([2246239](https://github.com/thomiOmi/laravel-starterkit-api/commit/224623915a72bf28589beb01b98ddb1230f5a0cd))
- **config:** translate Indonesian docstrings to English ([6d6fb7a](https://github.com/thomiOmi/laravel-starterkit-api/commit/6d6fb7a2159938681433935e23305e3693151d91))
- **readme:** align installation steps with PHP 8.4 + Windows setup ([7caba7d](https://github.com/thomiOmi/laravel-starterkit-api/commit/7caba7d98b90a8d3a7650af106cce2a7ab698616))
- add testing rules and enforcement guidelines to AGENTS.md ([c7fc0b5](https://github.com/thomiOmi/laravel-starterkit-api/commit/c7fc0b5d4d975142b10c2164a6aeefafbbc4cf06))
- add Sanctum auth decision and session history to KNOWLEDGE.md ([58bbf35](https://github.com/thomiOmi/laravel-starterkit-api/commit/58bbf35980bc0f601086e5b11f29fdde031b6570))
- **auth:** add anti-enumeration comment to ForgotPasswordAction ([364b3fc](https://github.com/thomiOmi/laravel-starterkit-api/commit/364b3fc25babd3b3329b63981101093a3557547a))
- **api:** update Scramble #[Response] examples to match new envelope ([4ac292d](https://github.com/thomiOmi/laravel-starterkit-api/commit/4ac292da3ee724d5ea830905138a5e4bcd6f87c4))
- improve API documentation with complete error responses and structured ProblemResponse schemas ([ba45839](https://github.com/thomiOmi/laravel-starterkit-api/commit/ba4583981efc3d4a468803d73828523b89216e91))
- add boost:install command to guidelines footer ([c9b66a2](https://github.com/thomiOmi/laravel-starterkit-api/commit/c9b66a270af8aae6fcc1bcc8ec8f2bbba5c18392))
- add guidelines for creating custom AI skills and rules ([fabc32e](https://github.com/thomiOmi/laravel-starterkit-api/commit/fabc32e473a621c76403911e0ec36ccb5d2f272c))
- add .ai/AGENTS.md with Boost MCP tools reference ([7120050](https://github.com/thomiOmi/laravel-starterkit-api/commit/71200506ea34f346ea89d326f72ef109ed85023b))
- add module tree structure to README and architecture.md ([4a3eda0](https://github.com/thomiOmi/laravel-starterkit-api/commit/4a3eda0e5034838004039595920feef36ed0b02c))
- restore architecture.md and coding-standards.md for developers ([7e37078](https://github.com/thomiOmi/laravel-starterkit-api/commit/7e37078493c16d49f99f94c8c0d46bce406d4bea))
- rewrite README, docs/, and .ai/ to reflect current codebase ([f78d4f3](https://github.com/thomiOmi/laravel-starterkit-api/commit/f78d4f33523eb327d34726263a50e83a1fc9a9a6))

### Tests

- **coverage:** add unit tests for all action classes, enable --parallel and --coverage ([60740bc](https://github.com/thomiOmi/laravel-starterkit-api/commit/60740bcb8f0a4423ee5e2b8fa96a10ec6d58603f))
- add sad-path coverage across all feature tests ([ebaddae](https://github.com/thomiOmi/laravel-starterkit-api/commit/ebaddae737dd9481fa90f81149a8125b88794b1f))
- add feature tests for 6 uncovered controllers ([9c43d56](https://github.com/thomiOmi/laravel-starterkit-api/commit/9c43d56ec55f1e892e64cf14c372db0c3b2aba2d))
- **i.am:** convert Socialite Mockery tests to Socialite::fake(), fix ModuleServiceTest ([7aa9ed6](https://github.com/thomiOmi/laravel-starterkit-api/commit/7aa9ed6abdb655ec4e77e05d0ed3976c0454db73))
- add Action/Repository/PermissionFilter unit tests and refactor SocialLoginTest ([334f9bc](https://github.com/thomiOmi/laravel-starterkit-api/commit/334f9bc77e543c48f1cfe7c9a58a9d47c07ead00))
- improve test quality with Laravel faking utilities and fix DB setup for Unit tests ([0f91fbf](https://github.com/thomiOmi/laravel-starterkit-api/commit/0f91fbf573673c90a062fcf14de74784d2bd1eea))
- **arch:** implement architecture tests to enforce Golden Standard ([71ca946](https://github.com/thomiOmi/laravel-starterkit-api/commit/71ca94663402df4ae38bb1aaeea40de8f0b60518))
- **user:** fix BulkActionTest to match new specialized bulk routes ([6af0f20](https://github.com/thomiOmi/laravel-starterkit-api/commit/6af0f205b83e17748bb1806a19aa0b64f832e6fb))

### Continuous Integration

- **type-coverage:** add Pest type coverage enforcement ([1eaff05](https://github.com/thomiOmi/laravel-starterkit-api/commit/1eaff0545d69f5bb407836a0390638ba9de30f6e))
- simplify laravel.yml to sqlite-only, add Spatie cache race to roadmap and knowledge ([f549d4f](https://github.com/thomiOmi/laravel-starterkit-api/commit/f549d4f18e509f22e94b95b3f411f86fc0f1fd19))
- enhance laravel.yml with composer validate, parallel tests, and audit ([78bf809](https://github.com/thomiOmi/laravel-starterkit-api/commit/78bf809b8f2631b7bcfcdb50cc8bac9cdb1e5cc0))
- pin MySQL 8.4 and Redis 8.6 to match local versions ([79319c4](https://github.com/thomiOmi/laravel-starterkit-api/commit/79319c4540e14f614e16de58704b89e3419000b9))
- remove PHP 8.3 from matrix (project requires >= 8.4) ([295fe51](https://github.com/thomiOmi/laravel-starterkit-api/commit/295fe51bf6af196cf740da82d5ffa7714a4c9bbf))
- update Laravel workflow with MySQL, Redis, and PHP matrix ([4a7160f](https://github.com/thomiOmi/laravel-starterkit-api/commit/4a7160fdc69b2f52d32f3c4ada7e3ed371529559))
- add GitHub Actions workflow for tests ([bbe861e](https://github.com/thomiOmi/laravel-starterkit-api/commit/bbe861e61991fb9c98efb9ff898b39cd7513fb72))
- **workflows:** standardize cache, PHP setup, and fix CI auto-fix loop ([50c20fa](https://github.com/thomiOmi/laravel-starterkit-api/commit/50c20fa3dd3a89292d84e331c36718ca2b663256))
- move agent workflows out of subdirectory and fix action refs ([819ace6](https://github.com/thomiOmi/laravel-starterkit-api/commit/819ace6aa8c6a642254a67d5e86543dc49944325))
- update release workflow to manual trigger and extract notes from CHANGELOG.md ([2636ce0](https://github.com/thomiOmi/laravel-starterkit-api/commit/2636ce0455bb73e1c6908f6e4d5053fdc1a08ac1))
- update release workflow to manual trigger and extract notes from CHANGELOG.md ([374f7b9](https://github.com/thomiOmi/laravel-starterkit-api/commit/374f7b9d70b7a01fc26b3614f79ee8a2b93df262))

### Chores

- **deps:** bump the php-dependencies group with 3 updates ([b29ab5d](https://github.com/thomiOmi/laravel-starterkit-api/commit/b29ab5d7cbc4f4190ba155ac5a04f55c21071a45))
- sync skill files across agent directories ([05df119](https://github.com/thomiOmi/laravel-starterkit-api/commit/05df119296ac4ed2983b073308cdb772edb8497a))
- **scramble:** remove custom extensions, cleanup config and Scramble annotations ([7774990](https://github.com/thomiOmi/laravel-starterkit-api/commit/7774990465100ce12957ec6f9bd47dc5d1602445))
- fix CI and update modular architecture documentation ([d95c7f0](https://github.com/thomiOmi/laravel-starterkit-api/commit/d95c7f0ebf8bbab6e6e65f07f0608356b764d0df))
- fix CI PHPStan error and cleanup obsolete documentation ([9322848](https://github.com/thomiOmi/laravel-starterkit-api/commit/9322848b72072fe525acb1af4e39517bbbc7e272))
- fix PHPStan property type coverage in User model ([53a9b7e](https://github.com/thomiOmi/laravel-starterkit-api/commit/53a9b7e183a99fb0f90ac33cf0bd900240a8a05d))
- fix PHPStan property type coverage in User model ([feea688](https://github.com/thomiOmi/laravel-starterkit-api/commit/feea688cc46d5dd9012151a925745da7e805ffcd))
- **deps:** bump spatie/laravel-permission ([dd25d07](https://github.com/thomiOmi/laravel-starterkit-api/commit/dd25d0739c3bc91ab740015458c2a021fcb78e1a))
- **deps:** bump actions/cache from 5 to 6 ([8bef0ed](https://github.com/thomiOmi/laravel-starterkit-api/commit/8bef0edc8716bbe8611a2b67244ceb9359e807a5))
- remove strict types declaration from configuration files ([1931687](https://github.com/thomiOmi/laravel-starterkit-api/commit/19316876679f570197ed1f63827523edad7c1677))
- remove unused withEvents discovery in bootstrap/app.php ([15b823a](https://github.com/thomiOmi/laravel-starterkit-api/commit/15b823ac52bcdd5ef9cf2397df88b7517cf1e679))
- schedule auth:clear-resets every 15 minutes ([2abaf17](https://github.com/thomiOmi/laravel-starterkit-api/commit/2abaf17859599185e5ff815ceb08ef10bda347e0))
- remove peststan, exclude tests dir from phpstan ([424184e](https://github.com/thomiOmi/laravel-starterkit-api/commit/424184e8e8324135b1666faf0c5b7228608577ee))
- update composer name, description, and lock pennant version ([b9236f4](https://github.com/thomiOmi/laravel-starterkit-api/commit/b9236f4faa6fbd1c8e563b7a5da0fa85b3063267))
- add laravel-verification skill to .agents from boost sync ([b2ebfb3](https://github.com/thomiOmi/laravel-starterkit-api/commit/b2ebfb3be0141e21ff8c8a7c6809da995ba51d5a))
- add PAO_FORCE guidelines, fix skills validation, add file ownership rules ([d404251](https://github.com/thomiOmi/laravel-starterkit-api/commit/d404251bc0f44241aaaf61e3a9bd3997b702d281))
- **deps:** composer update; sync skills and workflow via boost:install ([7137470](https://github.com/thomiOmi/laravel-starterkit-api/commit/7137470383b293235797333b8d9b6e46cadc07de))
- remove dead Scramble references from stubs, docs, and readme ([ecfebd4](https://github.com/thomiOmi/laravel-starterkit-api/commit/ecfebd41ee25438b9b6e46d75c5934b0322154aa))
- remove orphaned UserCreated event and related references ([f5c6640](https://github.com/thomiOmi/laravel-starterkit-api/commit/f5c664058e08601b9f4df482d99701a62af0ac0e))
- remove Scramble API docs and clean up unused attributes ([b6041b8](https://github.com/thomiOmi/laravel-starterkit-api/commit/b6041b88e8acb6898163fbd659ceb17a79e038d2))
- fork fresh cloud skills via boost:add-skill ([5881724](https://github.com/thomiOmi/laravel-starterkit-api/commit/588172459d6d289e56aed490e5c59419b6f41d45))
- add .zcode to gitignore ([3990bdf](https://github.com/thomiOmi/laravel-starterkit-api/commit/3990bdfb90ea5058669dadadda33011e76d223fa))
- add .zcode config ([4fc6424](https://github.com/thomiOmi/laravel-starterkit-api/commit/4fc6424628679652360a4e60652b6253b978fd7d))
- add .zcode/ to .gitignore ([5e270e1](https://github.com/thomiOmi/laravel-starterkit-api/commit/5e270e1518310a2c3770ec8e52c3d3da0b66fb7a))
- remove legacy agent workflows replaced by new Jules agents ([2208a05](https://github.com/thomiOmi/laravel-starterkit-api/commit/2208a0577a45d3411a7584daf3cc722fa9f5a0ad))
- sync Boost resources and skills via php artisan boost:install -n ([d55d054](https://github.com/thomiOmi/laravel-starterkit-api/commit/d55d054b82af447c8645547f8c7d109bdf18a0ef))
- remove .ai/AGENTS.md, info is in guidelines/general.md ([e51f5aa](https://github.com/thomiOmi/laravel-starterkit-api/commit/e51f5aa094a552efeb4943531ee1bb2c26d59ba1))
- sync Boost resources via boost:update --discover ([493649b](https://github.com/thomiOmi/laravel-starterkit-api/commit/493649b5f1588df5cd374365191c5e8082bf3ef9))
- composer update, Pint cleanup, remove APP_KEY from phpunit.xml ([e0ea354](https://github.com/thomiOmi/laravel-starterkit-api/commit/e0ea354f2ae63f45b051986e118985ba0a72ff92))
- code cleanup and dead code removal ([87af5cb](https://github.com/thomiOmi/laravel-starterkit-api/commit/87af5cbcc724661786a4f03433151e29c17ff188))
- **deps:** Bump the php-dependencies group with 3 updates ([7100756](https://github.com/thomiOmi/laravel-starterkit-api/commit/7100756f45623563af5a7ed84246e64ff08ba4d0))
- remove tenancy reference from tech stack documentation ([6b106f4](https://github.com/thomiOmi/laravel-starterkit-api/commit/6b106f4b10f0eb069f6091d2a30a815965774023))
- audit and refactor codebase to Laravel 13 standards ([044a62d](https://github.com/thomiOmi/laravel-starterkit-api/commit/044a62d89c6293895098cff71a98be85b41c0b38))
- **deps:** Bump the php-dependencies group with 7 updates ([97b175f](https://github.com/thomiOmi/laravel-starterkit-api/commit/97b175faf5fef0e2b68a8674da17543c63f3dcd9))
- **deps:** Bump actions/cache from 4 to 5 ([223f3e7](https://github.com/thomiOmi/laravel-starterkit-api/commit/223f3e760d30613dd90639a626fa0ef7284aef36))

### Reverts

- restore Spatie cache key to default (docs advise against changing it) ([23a4be3](https://github.com/thomiOmi/laravel-starterkit-api/commit/23a4be35f25cd2ec6696bd522e5a0767148a5b33))

### Styles

- **i.am:** fix blank line after guard_name removal in User model ([71a20a3](https://github.com/thomiOmi/laravel-starterkit-api/commit/71a20a30f0df09eaabbc98f7b7b5f84665732b79))
- fix code style issues and imports across controllers ([63b0dec](https://github.com/thomiOmi/laravel-starterkit-api/commit/63b0dec29fe49aeac16334cb664a773653bb7311))
- clean up tree structure format ([6427056](https://github.com/thomiOmi/laravel-starterkit-api/commit/6427056a9dee5099741199bffba4bda8d52a8b89))
## [0.18.2](https://github.com/thomiOmi/laravel-starterkit-api/releases/tag/v0.18.2) (2026-05-28)

### Code Refactoring

- expand and detail agent skills with technical references ([9673490](https://github.com/thomiOmi/laravel-starterkit-api/commit/967349044a04b25ffe5fc1a1b76594676e205458))
## [0.18.1](https://github.com/thomiOmi/laravel-starterkit-api/releases/tag/v0.18.1) (2026-05-28)

### Code Refactoring

- align agent skills with agentskills.io folder structure ([10fb9cf](https://github.com/thomiOmi/laravel-starterkit-api/commit/10fb9cf6b7d5a9314f7ba85e314594bdd9188e01))
- rename and detail agent skills to Standard 2026 ([25b6847](https://github.com/thomiOmi/laravel-starterkit-api/commit/25b68475d2c399a3bfcc11192ad5858eda6bde27))
- replace api-skill with modular laravel-boost skills ([6f486d9](https://github.com/thomiOmi/laravel-starterkit-api/commit/6f486d9f93d3ecd24a8d0fd0be4758b9de3fa1f3))
## [0.18.0](https://github.com/thomiOmi/laravel-starterkit-api/releases/tag/v0.18.0) (2026-05-25)

### Features

- consolidate architecture tests and enhance project standards ([9a6ad4e](https://github.com/thomiOmi/laravel-starterkit-api/commit/9a6ad4e1fcc87f571b87ed31af300056590574eb))
- enhance scalability, code quality, and developer experience ([6d9b322](https://github.com/thomiOmi/laravel-starterkit-api/commit/6d9b32220fc8d19eb9fbe99201f3b9d6d0baf4c2))
## [0.17.0](https://github.com/thomiOmi/laravel-starterkit-api/releases/tag/v0.17.0) (2026-05-25)

### Features

- add custom guidelines and skills ([db1aa2b](https://github.com/thomiOmi/laravel-starterkit-api/commit/db1aa2bfdad0a18608c39a195f06945d0ff42705))
## [0.16.0](https://github.com/thomiOmi/laravel-starterkit-api/releases/tag/v0.16.0) (2026-05-25)

### Features

- **ci:** improve github workflows and fix release error ([f7acec0](https://github.com/thomiOmi/laravel-starterkit-api/commit/f7acec06b97854ca8495a24548a229bcaae754d5))
- **ci:** improve github workflows with best practices ([1e22459](https://github.com/thomiOmi/laravel-starterkit-api/commit/1e2245961be52f3d912280ed56954213f68c926d))
## [0.15.0](https://github.com/thomiOmi/laravel-starterkit-api/releases/tag/v0.15.0) (2026-05-25)

### Features

- added github workflows for bug fixer. permonance checker, security checker ([6cb3bc2](https://github.com/thomiOmi/laravel-starterkit-api/commit/6cb3bc2c0229f0e83527b1b4f7bdccc7f7b40f26))
## [0.14.2](https://github.com/thomiOmi/laravel-starterkit-api/releases/tag/v0.14.2) (2026-05-25)

### Chores

- **deps:** Bump actions/github-script from 7 to 9 ([23b20ac](https://github.com/thomiOmi/laravel-starterkit-api/commit/23b20ac3f674e70118af3ca0871dade5ec46fcdb))
## [0.14.1](https://github.com/thomiOmi/laravel-starterkit-api/releases/tag/v0.14.1) (2026-05-25)

### Chores

- **deps:** Bump the php-dependencies group with 3 updates ([6bd8470](https://github.com/thomiOmi/laravel-starterkit-api/commit/6bd84705f225838847fe4be26e769cbc14078103))
## [0.14.0](https://github.com/thomiOmi/laravel-starterkit-api/releases/tag/v0.14.0) (2026-05-25)

### Features

- add support for auto-update composer packages via GitHub Dependabot ([eefa05c](https://github.com/thomiOmi/laravel-starterkit-api/commit/eefa05cca3c95e2815dc7864878796a49ef52607))
## [0.13.2](https://github.com/thomiOmi/laravel-starterkit-api/releases/tag/v0.13.2) (2026-05-25)

### Chores

- codebase maintenance and refactoring ([c47e8f9](https://github.com/thomiOmi/laravel-starterkit-api/commit/c47e8f9bd2209394ba31a4f7f242fd8a3e8c9cfc))
## [0.13.1](https://github.com/thomiOmi/laravel-starterkit-api/releases/tag/v0.13.1) (2026-05-19)

### Code Refactoring

- move bulk action logic to dedicated classes and cleanup service providers ([473f84c](https://github.com/thomiOmi/laravel-starterkit-api/commit/473f84cf0dd5b8d82d257c65031cfb3e3fced2aa))
## [0.13.0](https://github.com/thomiOmi/laravel-starterkit-api/releases/tag/v0.13.0) (2026-05-19)

### Features

- implement multi-language API responses ([3ef4df8](https://github.com/thomiOmi/laravel-starterkit-api/commit/3ef4df8f0cc8644502a5eadcc59d089fc0f049a8))
## [0.12.1](https://github.com/thomiOmi/laravel-starterkit-api/releases/tag/v0.12.1) (2026-05-19)

### Code Refactoring

- audit and align code with 2026 standards ([8cb6187](https://github.com/thomiOmi/laravel-starterkit-api/commit/8cb6187380e4776fb2d40fd09d10226487695c41))

### Chores

- cleanup dead lang files and switch to scoped keys ([f2d96f9](https://github.com/thomiOmi/laravel-starterkit-api/commit/f2d96f98f49a005ec19195b9126c0573e761c1bb))
- remove unused language files ([3c3df29](https://github.com/thomiOmi/laravel-starterkit-api/commit/3c3df29d8f8330232fd34c79667d5f37e89436f5))
## [0.12.0](https://github.com/thomiOmi/laravel-starterkit-api/releases/tag/v0.12.0) (2026-05-19)

### Features

- implement Laravel 2026 technical foundations ([67dbfc0](https://github.com/thomiOmi/laravel-starterkit-api/commit/67dbfc04cdafb7b23379162d0696ce5c8020d588))
## [0.11.0](https://github.com/thomiOmi/laravel-starterkit-api/releases/tag/v0.11.0) (2026-05-19)

### Features

- upgrade api-skill to Laravel 2026 standards (English) ([c2e4012](https://github.com/thomiOmi/laravel-starterkit-api/commit/c2e4012eceea79850536675bd90537fe2f59718c))
- upgrade api-skill to Laravel 2026 standards ([69c1ff4](https://github.com/thomiOmi/laravel-starterkit-api/commit/69c1ff446304b2615e961e7bf6c6a811ffad31d2))
## [0.10.0](https://github.com/thomiOmi/laravel-starterkit-api/releases/tag/v0.10.0) (2026-05-18)

### Features

- improve make:module with api versioning and single-action controllers ([9549813](https://github.com/thomiOmi/laravel-starterkit-api/commit/9549813035ecfad44a560e65c3b0b65798e3d4ae))
## [0.9.1](https://github.com/thomiOmi/laravel-starterkit-api/releases/tag/v0.9.1) (2026-05-17)

### Code Refactoring

- dead code cleanup and generator update ([fc204e3](https://github.com/thomiOmi/laravel-starterkit-api/commit/fc204e386ab0f8e67aafaa5a8ec87e1591552dc5))
## [0.9.0](https://github.com/thomiOmi/laravel-starterkit-api/releases/tag/v0.9.0) (2026-05-17)

### Features

- implement new AI skill standards across codebase ([9b71c86](https://github.com/thomiOmi/laravel-starterkit-api/commit/9b71c869fece8a2c5a5a4dbb72aa48ba1b4a8f77))
## [0.8.0](https://github.com/thomiOmi/laravel-starterkit-api/releases/tag/v0.8.0) (2026-05-17)

### Features

- **ai-skill:** final overhaul of api-skill and synchronization of Sunset middleware ([a7eeb9f](https://github.com/thomiOmi/laravel-starterkit-api/commit/a7eeb9fa1baedbf035f63d477dc65eb04b442637))
- **ai-skill:** finalize api-skill overhaul with JSK standards and project adaptations ([f7b9dae](https://github.com/thomiOmi/laravel-starterkit-api/commit/f7b9daee358691b9c4cfd1acd820ec42c10fa13f))
- **ai-skill:** comprehensive overhaul of api-skill with project-specific standards ([bf5c871](https://github.com/thomiOmi/laravel-starterkit-api/commit/bf5c87199c841994779af2ff4854640fb14957aa))
- **ai-skill:** total overhaul of api-skill based on high-standards ([cfc21c8](https://github.com/thomiOmi/laravel-starterkit-api/commit/cfc21c8d3fcdbb9c3c184aecea08aeb382398775))
- **ai-skill:** overhaul api-skill with comprehensive modular standards ([f3e5304](https://github.com/thomiOmi/laravel-starterkit-api/commit/f3e5304c1222acc307ed949780a9610fa827ae2f))
## [0.7.3](https://github.com/thomiOmi/laravel-starterkit-api/releases/tag/v0.7.3) (2026-05-17)

### Bug Fixes

- resolve PHPStan 'offsetAccess.nonOffsetAccessible' errors in JsonDataResponse ([b148ae7](https://github.com/thomiOmi/laravel-starterkit-api/commit/b148ae715aec63fad2de214513eabe617e062320))
## [0.7.2](https://github.com/thomiOmi/laravel-starterkit-api/releases/tag/v0.7.2) (2026-05-17)

### Bug Fixes

- resolve all CI failures and align with existing test suite ([2e7d97a](https://github.com/thomiOmi/laravel-starterkit-api/commit/2e7d97af960c71dfcde2ee47e0f1e372ce02fb63))
- align refactored components with existing test suite ([9f0ea57](https://github.com/thomiOmi/laravel-starterkit-api/commit/9f0ea5762937d1d6ccb30c43edadbf5dce443a16))
- resolve PHPStan type casting issue in BulkDeleteUserAction ([04daeb3](https://github.com/thomiOmi/laravel-starterkit-api/commit/04daeb3da83f4e750d195dc41a4c981c2f7e6983))
- address PHPStan and CI failures after refactor ([855ffa9](https://github.com/thomiOmi/laravel-starterkit-api/commit/855ffa916c04b7a17df77752e6e288491e3dfda8))

### Code Refactoring

- implement api-skill standards across User, Role, and Auth modules ([0c3c647](https://github.com/thomiOmi/laravel-starterkit-api/commit/0c3c64790130738b767ea728e54344f81aa97a3f))
## [0.7.1](https://github.com/thomiOmi/laravel-starterkit-api/releases/tag/v0.7.1) (2026-05-17)
## [0.7.0](https://github.com/thomiOmi/laravel-starterkit-api/releases/tag/v0.7.0) (2026-05-16)

### Features

- refactor API responses to classes and sync AI Skill ([cd48cfd](https://github.com/thomiOmi/laravel-starterkit-api/commit/cd48cfdecb292606aee9e1295bd645d5bdc85026))
## [0.6.0](https://github.com/thomiOmi/laravel-starterkit-api/releases/tag/v0.6.0) (2026-05-16)

### Features

- ultimate detailed and versioned custom AI Skill ([dd63f33](https://github.com/thomiOmi/laravel-starterkit-api/commit/dd63f3339c430505d9aa6f5c09abc71cb44a5b9e))
- implement final consolidated and versioned AI Skill ([a883eb6](https://github.com/thomiOmi/laravel-starterkit-api/commit/a883eb61ec78d070a99e5a592939cb7f86e202f7))
## [0.5.0](https://github.com/thomiOmi/laravel-starterkit-api/releases/tag/v0.5.0) (2026-05-16)

### Features

- final consolidated and versioned custom AI Skill ([2927d2e](https://github.com/thomiOmi/laravel-starterkit-api/commit/2927d2e93ec6b59ddd167e45311589cba3b1426b))
- implement custom AI skill with consolidated conventions ([232e92c](https://github.com/thomiOmi/laravel-starterkit-api/commit/232e92ca5dba8afd36752f3f351a686ec479657c))
- implement comprehensive custom AI Skill for modular API ([cab06a4](https://github.com/thomiOmi/laravel-starterkit-api/commit/cab06a48d0c57e69236b0169bfb36583eeee40a7))
## [0.4.0](https://github.com/thomiOmi/laravel-starterkit-api/releases/tag/v0.4.0) (2026-05-15)

### Features

- ultimate optimized custom AI guidelines for modular API ([6f16c96](https://github.com/thomiOmi/laravel-starterkit-api/commit/6f16c96aeb5fc97aab5f2e0b2ccbfc05a454b3be))
- final comprehensive AI guidelines for modular API ([29fa481](https://github.com/thomiOmi/laravel-starterkit-api/commit/29fa4811d4668a694f1c3a650bea887f6bc6bdda))
- final comprehensive modular AI guidelines ([0e65303](https://github.com/thomiOmi/laravel-starterkit-api/commit/0e6530344df5b661879b5c0ea4a5576001c11763))
- final comprehensive custom AI guidelines ([6569847](https://github.com/thomiOmi/laravel-starterkit-api/commit/6569847f39ce650509d352015ede73f460dd3a1b))
- complete custom AI guidelines for modular API ([258b540](https://github.com/thomiOmi/laravel-starterkit-api/commit/258b54073157a5636b12869ce75045bbd6316943))
- add comprehensive custom AI guidelines based on api-skill ([084f288](https://github.com/thomiOmi/laravel-starterkit-api/commit/084f288133c1c2c93a98b5ee91976f534457de1d))
- add custom AI guidelines based on api-skill ([725df4e](https://github.com/thomiOmi/laravel-starterkit-api/commit/725df4e4f7d55f1ac2d5d4ee69ebc1c6f0f1035e))
## [0.3.0](https://github.com/thomiOmi/laravel-starterkit-api/releases/tag/v0.3.0) (2026-05-14)

### Features

- audit code, refactor module generator, and update documentation ([7df6b14](https://github.com/thomiOmi/laravel-starterkit-api/commit/7df6b1444d9ca2e93d4cc43300f41fd79ea2a49d))
## [0.2.9](https://github.com/thomiOmi/laravel-starterkit-api/releases/tag/v0.2.9) (2026-05-14)

### Bug Fixes

- resolve CI failure by addressing PHPStan Level 9 errors ([1839a74](https://github.com/thomiOmi/laravel-starterkit-api/commit/1839a742ad37b3fe24180038e406cca578ffb4b4))
## [0.2.8](https://github.com/thomiOmi/laravel-starterkit-api/releases/tag/v0.2.8) (2026-05-14)

### Chores

- Update laravel.yml ([94d1f4c](https://github.com/thomiOmi/laravel-starterkit-api/commit/94d1f4c1145d3d3ee2e3e642bdc77701a6077afe))
## [0.2.7](https://github.com/thomiOmi/laravel-starterkit-api/releases/tag/v0.2.7) (2026-05-14)

### Bug Fixes

- address PHPStan Level 9 and Pint violations in User module ([e66fdc0](https://github.com/thomiOmi/laravel-starterkit-api/commit/e66fdc01404f52125501911fa3d0d1791416d172))
- resolve CI failure by fixing PHPStan config, types, and architecture ([f303074](https://github.com/thomiOmi/laravel-starterkit-api/commit/f303074add82352756485d5ef84a18cbb67e37d0))
- resolve CI failure by fixing PHPStan configuration and type errors ([4c17882](https://github.com/thomiOmi/laravel-starterkit-api/commit/4c17882704b30874f433a856dd746e6c933f69b7))
- remove obsolete checkGenericClassInNonGenericObjectType from phpstan.neon ([f71626e](https://github.com/thomiOmi/laravel-starterkit-api/commit/f71626eb034362c55a0b2e8223f6abffaeb10cb4))
## [0.2.6](https://github.com/thomiOmi/laravel-starterkit-api/releases/tag/v0.2.6) (2026-05-14)

### Bug Fixes

- Update laravel.yml ([f09f2a1](https://github.com/thomiOmi/laravel-starterkit-api/commit/f09f2a1e89dc709152a34b970cbf95bb524e4910))
## [0.2.5](https://github.com/thomiOmi/laravel-starterkit-api/releases/tag/v0.2.5) (2026-05-14)

### Bug Fixes

- Update phpstan.neon ([80a3bf3](https://github.com/thomiOmi/laravel-starterkit-api/commit/80a3bf3aa3cec594713140321d409ae5445489e6))
## [0.2.4](https://github.com/thomiOmi/laravel-starterkit-api/releases/tag/v0.2.4) (2026-05-14)

### Bug Fixes

- Update release.yml ([24eb68d](https://github.com/thomiOmi/laravel-starterkit-api/commit/24eb68d09b3cbb83fbf97089ba51ede929fbfe3e))
## [0.2.3](https://github.com/thomiOmi/laravel-starterkit-api/releases/tag/v0.2.3) (2026-05-14)

### Chores

- codebase cleanup and filtering standardization ([43ee99e](https://github.com/thomiOmi/laravel-starterkit-api/commit/43ee99e27e5fbbf4c86c81d639121f6ce2807152))
- codebase cleanup and filtering standardization ([a3088c3](https://github.com/thomiOmi/laravel-starterkit-api/commit/a3088c339ff97d842f6fe8523e9efd6ca8ec154a))
## [0.2.2](https://github.com/thomiOmi/laravel-starterkit-api/releases/tag/v0.2.2) (2026-05-13)

### Bug Fixes

- Update release.yml ([d3a3647](https://github.com/thomiOmi/laravel-starterkit-api/commit/d3a3647adf6ef17489056fa1451831b5e1c83e2d))
## [0.2.1](https://github.com/thomiOmi/laravel-starterkit-api/releases/tag/v0.2.1) (2026-05-13)

### Chores

- fix release tagging identity by adding explicit git config ([555f408](https://github.com/thomiOmi/laravel-starterkit-api/commit/555f4081fef428600d2b5a316f73b2fb278f844d))
## [0.2.0](https://github.com/thomiOmi/laravel-starterkit-api/releases/tag/v0.2.0) (2026-05-13)

### Features

- Create weekly-cleanup.yml ([9fad975](https://github.com/thomiOmi/laravel-starterkit-api/commit/9fad97539c98fced5b54a15b4a888c5c6d0a5f2a))
## [0.1.2](https://github.com/thomiOmi/laravel-starterkit-api/releases/tag/v0.1.2) (2026-05-13)

### Chores

- Update release.yml ([26863bc](https://github.com/thomiOmi/laravel-starterkit-api/commit/26863bcb33631fd8ec01dfe6ff8b339e0d91ad7c))
- update release workflow with official bot identity and auth ([e845e06](https://github.com/thomiOmi/laravel-starterkit-api/commit/e845e0637a721ad822c74181c15819722d411daa))
- final adjustments to release workflow configuration ([7e0cfbc](https://github.com/thomiOmi/laravel-starterkit-api/commit/7e0cfbc2d5691b53e09ef99decb21306776047d8))
## [0.1.1](https://github.com/thomiOmi/laravel-starterkit-api/releases/tag/v0.1.1) (2026-05-13)

### Chores

- fix release workflow argument and address actions deprecations ([1ad6a77](https://github.com/thomiOmi/laravel-starterkit-api/commit/1ad6a7764c7e18e5482b42fd23f5cebe1c3ce404))
- Update ci-failure-fix.yml ([6c6306e](https://github.com/thomiOmi/laravel-starterkit-api/commit/6c6306eca3141bb3068b5c0eb4a83215669e5191))
- update github actions for node 24 and fix workflow triggers ([f79e6f8](https://github.com/thomiOmi/laravel-starterkit-api/commit/f79e6f8787423cef77ff1733b2336621db7b409a))
## [0.1.0](https://github.com/thomiOmi/laravel-starterkit-api/releases/tag/v0.1.0) (2026-05-13)

### Features

- Phase 4 - Ecosystem, Security & API Documentation ([f92de1c](https://github.com/thomiOmi/laravel-starterkit-api/commit/f92de1cbd3010b694cf29db91f4658cea26c1daa))
- core infrastructure refinement and auth/role module audit (fase 3) ([180fe46](https://github.com/thomiOmi/laravel-starterkit-api/commit/180fe46ede151dd17c05bd164249116ad5af9c34))
- remove two-factor authentication support ([3a6a17a](https://github.com/thomiOmi/laravel-starterkit-api/commit/3a6a17a66d656421e1c3fcbb421c75c6bfc94511))
- add pennant-development skill and update AGENTS.md and boost.json ([fa2e681](https://github.com/thomiOmi/laravel-starterkit-api/commit/fa2e681dd6ebf720840bd51cc654a51c4962b448))
- implement media library, device management, and enterprise webhook enhancements ([d0bb714](https://github.com/thomiOmi/laravel-starterkit-api/commit/d0bb714d27c0420ae5543cbb933b7caa38150ec0))
- implement media library, device management, and enterprise webhook enhancements ([e79cfe4](https://github.com/thomiOmi/laravel-starterkit-api/commit/e79cfe4cb9a194eac1b92ff0e0b359d7bd88c6cc))
- implement media library, device management, and enterprise webhook enhancements ([8c8dd75](https://github.com/thomiOmi/laravel-starterkit-api/commit/8c8dd756a6be5643d6bd409f7df70f2a314f3b38))
- implement media library, device management, and enterprise webhook enhancements ([59dec19](https://github.com/thomiOmi/laravel-starterkit-api/commit/59dec19d9a6b09bac984b54e46c6a95ee2423ebe))
- implement media library, device management, and enterprise webhook enhancements ([4122259](https://github.com/thomiOmi/laravel-starterkit-api/commit/41222592f40f1be15f095e92fc4958a3bb42939c))
- implement subscription management system ([3562e9e](https://github.com/thomiOmi/laravel-starterkit-api/commit/3562e9e2b66b6106a6fed4a3c08c935ca5edecbb))
- implement security pack (2FA and password policy engine) ([5c254c7](https://github.com/thomiOmi/laravel-starterkit-api/commit/5c254c7ea49c36ac730dfa0d867d0799b40514c6))
- implement SaaS B2B multi-tenancy and enterprise features ([6c4d4a1](https://github.com/thomiOmi/laravel-starterkit-api/commit/6c4d4a15c96d64f19a644557e6880870970aeb1d))
- implement api key management and finalize enterprise features ([3bd57ed](https://github.com/thomiOmi/laravel-starterkit-api/commit/3bd57ede282adc9c90f0f25d392c3caac419438e))
- implement api key management and finalize enterprise audit logs ([64d425d](https://github.com/thomiOmi/laravel-starterkit-api/commit/64d425d16d69d909642ffaa4e386ea40371cbf25))
- implement modular audit log (v4 compatibility) ([ec81dff](https://github.com/thomiOmi/laravel-starterkit-api/commit/ec81dff7616d8de42155de1cdda5b61ffea6f150))
- implement modular audit log with spatie/laravel-activitylog ([3fbd590](https://github.com/thomiOmi/laravel-starterkit-api/commit/3fbd59048313ed4080a013da25a23c4490e1a043))
- implement audit logs for User and Role models ([cce083b](https://github.com/thomiOmi/laravel-starterkit-api/commit/cce083bb50d263c05ef597436c541266e873d8ea))
- implement api versioning and response localization + ci fixes ([9957f3f](https://github.com/thomiOmi/laravel-starterkit-api/commit/9957f3f7f9ae9190c92ed6d7e0a8ccd7ec7da4e6))
- implement api versioning and response localization ([87f0489](https://github.com/thomiOmi/laravel-starterkit-api/commit/87f0489a08baae7b2e82f05955232eef673e9c1c))
- implement advanced modular architecture and automated module generator ([62ccd68](https://github.com/thomiOmi/laravel-starterkit-api/commit/62ccd68c7eb1d996664f6babd613357931d1b353))
- implement module-based role management, authentication flows, and DataTable DTO support ([0adbae5](https://github.com/thomiOmi/laravel-starterkit-api/commit/0adbae5d15e5b46ff9945cf97358985dd257479e))
- add PR closed automation workflow and scaffold placeholder route files ([0a783a4](https://github.com/thomiOmi/laravel-starterkit-api/commit/0a783a421155f5494f150b0343069cd57d3b9dbf))
- Add GitHub Actions workflow for Laravel tests ([e505dce](https://github.com/thomiOmi/laravel-starterkit-api/commit/e505dcea86439fc640a9ea086dccae321ec68417))
- add Scramble, ShipMark, and manual API versioning ([4cdeede](https://github.com/thomiOmi/laravel-starterkit-api/commit/4cdeedec60145341ee0282bd705a67ffc3fc564a))
- add laravel-permission development skill and antigravity agent support ([fa9041b](https://github.com/thomiOmi/laravel-starterkit-api/commit/fa9041b6ca0ce6c8750bf5bafde286f7ead29fc6))
- implement automated API versioning, generic bulk actions, and module extensions ([d7e92eb](https://github.com/thomiOmi/laravel-starterkit-api/commit/d7e92eb8b9bb4d9143109493b60e6727541ec6c8))
- implement abstract BaseRepository and introduce Role and User module controllers with related actions ([724dc2f](https://github.com/thomiOmi/laravel-starterkit-api/commit/724dc2fa2c5d58a13d4d983f4a92a6b65beec2c6))
- implement base repository, data table DTO, API response trait, and Pest testing configuration ([2a4cf07](https://github.com/thomiOmi/laravel-starterkit-api/commit/2a4cf07e1a724bdba7db69a2f29f295de4e1a724))
- define authentication routes and remove agent configuration file ([4737a32](https://github.com/thomiOmi/laravel-starterkit-api/commit/4737a3206c42635ea6a995505a61b83d2355247f))
- **auth:** implement complete auth module with email verification and password reset ([fffff29](https://github.com/thomiOmi/laravel-starterkit-api/commit/fffff29c2ce8c3db87106f9c224b94e1c90c95ea))
- **auth:** implement auth module and refactor user model to use ulids ([b5d2ce9](https://github.com/thomiOmi/laravel-starterkit-api/commit/b5d2ce9bcd4f5fe0256281ae9419c930fb68689a))
- implement modular structure and sanctum authentication ([7e1d7a3](https://github.com/thomiOmi/laravel-starterkit-api/commit/7e1d7a3b6728d290a5f3c864e3a4d65a87475a93))

### Bug Fixes

- resolve remaining issues from fortify removal refactor ([6864a90](https://github.com/thomiOmi/laravel-starterkit-api/commit/6864a9076b2518308e5992b6e6be116a3cf406fc))
- CI failures, command bug, and final cleanup ([09f6308](https://github.com/thomiOmi/laravel-starterkit-api/commit/09f6308cbee809d90e9f9741ca9a3d5de703116d))
- CI failures and final cleanup ([c951f8a](https://github.com/thomiOmi/laravel-starterkit-api/commit/c951f8a3e04d76b6953af7c73e59a77232fe50fe))
- Add medialibrary development skill and reference guide; remove Antigravity agent and update configurations ([dc3def8](https://github.com/thomiOmi/laravel-starterkit-api/commit/dc3def8939583225fae35a445b85b4aea9cd1b48))
- resolve PHPStan analysis errors and property definitions ([b7c2dda](https://github.com/thomiOmi/laravel-starterkit-api/commit/b7c2dda4ec6e40eefc2025df5f56c70e710b628c))
- resolve UserRepository FatalError and improve module test discovery ([55bb3f9](https://github.com/thomiOmi/laravel-starterkit-api/commit/55bb3f92c77f9a7adcb2d1d3b35936c019bef1b2))
- Move role assignment to RoleSeeder, fix ULID migration, and define authenticated rate limiter ([724ab28](https://github.com/thomiOmi/laravel-starterkit-api/commit/724ab2877ff2e4ca0319112da39c4d83d7f7fad7))
- **user:** resolve RegisterUserAction type mismatch and rename repository method ([e848716](https://github.com/thomiOmi/laravel-starterkit-api/commit/e848716ae7759ec5bbda92d91f92900d3231772f))

### Code Refactoring

- audit and refine Auth module based on skill guidelines ([c3a8acd](https://github.com/thomiOmi/laravel-starterkit-api/commit/c3a8acd405dbd43488da87892bdd4c3170647746))
- comprehensive audit and refinement of User and Role modules ([49777d8](https://github.com/thomiOmi/laravel-starterkit-api/commit/49777d8070e7ceb7b41dd64b742f8d37fea9c958))
- audit and refine User and Role modules based on skill guidelines ([6721f9f](https://github.com/thomiOmi/laravel-starterkit-api/commit/6721f9f48ad870f7d8a679ff3c51793a012a9a7e))
- audit and refine User module based on skill guidelines ([6bc633f](https://github.com/thomiOmi/laravel-starterkit-api/commit/6bc633f14ab52e2c9fc679f12f95def5e6470ba6))
- audit and refine User module based on skill guidelines ([c8f3733](https://github.com/thomiOmi/laravel-starterkit-api/commit/c8f37333cc3468786edc7b0e8ff9ed956e7c933f))
- **auth:** remove fortify, implement custom auth and social login ([f037fa7](https://github.com/thomiOmi/laravel-starterkit-api/commit/f037fa787b2f7b4fd90be1be4c345e67584b18a6))
- improve architecture, type-safety, and API resource formatting ([98d9433](https://github.com/thomiOmi/laravel-starterkit-api/commit/98d9433601631d03681e51b6cac7713578bac222))
- remove redundant project documentation and consolidate agent configurations ([59bd5be](https://github.com/thomiOmi/laravel-starterkit-api/commit/59bd5be756cac97aff81b069cf0d012a38b522e1))
- **user:** update resource timestamps and seeder count; tweak repository pagination ([cd24118](https://github.com/thomiOmi/laravel-starterkit-api/commit/cd24118f156a637232c39bdc79b3313436896f3a))
- remove frontend dependencies and blade views ([e0b6c76](https://github.com/thomiOmi/laravel-starterkit-api/commit/e0b6c7625b2c70b1647f0bdbf120288b7910bf59))

### Documentation

- **agent:** improve Socialite skill docs and update PHP version metadata ([c1bc745](https://github.com/thomiOmi/laravel-starterkit-api/commit/c1bc745aa042c040c374762746c1948e6d504478))
- finalize enterprise SaaS documentation and hub ([cb49491](https://github.com/thomiOmi/laravel-starterkit-api/commit/cb494915862da9ba18b47e4f62db6d2f320bc5d8))
- update README and add comprehensive technical documentation ([e6bb52e](https://github.com/thomiOmi/laravel-starterkit-api/commit/e6bb52eb5844523c188a9f3d2b0712d75bd139f7))
- update default database in README and remove hardcoded APP_KEY from phpunit.xml ([1541107](https://github.com/thomiOmi/laravel-starterkit-api/commit/1541107fa6be8f1d899ce681f47261c0dd66a7c0))
- update README with comprehensive English documentation ([5c04bbd](https://github.com/thomiOmi/laravel-starterkit-api/commit/5c04bbd80e8121a8316dd77e845ac1fcd6864a13))

### Chores

- upgrade to PHP 8.4 and fix GitHub Actions deprecations ([08e041e](https://github.com/thomiOmi/laravel-starterkit-api/commit/08e041e57cfdff0d0e2a9f417d4ca4eee9380eff))
- update setup ([b746d48](https://github.com/thomiOmi/laravel-starterkit-api/commit/b746d48769ce5614e3c1036a09fa79870938cf54))
- cleanup code unused ([cab699e](https://github.com/thomiOmi/laravel-starterkit-api/commit/cab699e52117ccfd5a414a7da4d540a7fb113b77))
- remove pulse, telescope, tenancy, and media library ([e1507e7](https://github.com/thomiOmi/laravel-starterkit-api/commit/e1507e70323ce26ac521800dcf74741eafd6a105))
- remove file media test ([b25aa0b](https://github.com/thomiOmi/laravel-starterkit-api/commit/b25aa0b3ce0f1fa70010dafb2a77c2f81634298d))
- remove unused avatar and image files from tenant storage ([3595e15](https://github.com/thomiOmi/laravel-starterkit-api/commit/3595e15b1da46040c67635508209df8487e9b4ad))
- remove unused avatar and image files from tenant storage ([31c0c38](https://github.com/thomiOmi/laravel-starterkit-api/commit/31c0c38670e7174140d55a606de0df6712f50919))
- remove unused avatar and image files from tenant storage ([48c1bef](https://github.com/thomiOmi/laravel-starterkit-api/commit/48c1bef137a80ef5929af3a00e42403d4247dc78))
- add code review feedback for audit-logs v2 ([4c806a4](https://github.com/thomiOmi/laravel-starterkit-api/commit/4c806a4d8ce919d56ffc189d639f91728585b312))
- Update PR closed message to remove release note ([fc8b0e0](https://github.com/thomiOmi/laravel-starterkit-api/commit/fc8b0e0277a6da0fa762ac0d0d413443407b45c3))
- remove redundant API specification and test output files ([981a356](https://github.com/thomiOmi/laravel-starterkit-api/commit/981a3566becdf0f1ce754a80bf2aa79849af8cb8))
- Update Composer commands in Laravel workflow ([1729d46](https://github.com/thomiOmi/laravel-starterkit-api/commit/1729d4678f46e78dec52fd05ae3920fa6cfcac41))
- Update PHP version to 8.3 in workflow ([0bafc1d](https://github.com/thomiOmi/laravel-starterkit-api/commit/0bafc1dd20e0c2c0c53c0bfcb46f5849edf3da07))

### Styles

- fix code formatting issues detected by Pint in CI ([7c4306d](https://github.com/thomiOmi/laravel-starterkit-api/commit/7c4306d18925d06afd0420f78cff3a77fab8072f))
