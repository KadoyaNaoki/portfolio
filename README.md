# ポートフォリオサイト（ユーザー管理システム）

ポートフォリオとして制作した Web アプリケーションです。
「ユーザー管理」を題材に、**認証・認可（ロール）・履歴管理・バッチ処理・API** といった
実務でよく求められる要素を一通り実装し、設計（責務分離・保守性）を意識して作りました。

## URL
https://portfolio-07n7.onrender.com/
- ユーザーID：admin_test
- パスワード：testtest

## 目次

- [概要](#概要)
- [使用技術](#使用技術)
- [主な機能](#主な機能)
- [画面一覧](#画面一覧)
- [アーキテクチャ / ディレクトリ構成](#アーキテクチャ--ディレクトリ構成)
- [セットアップ手順](#セットアップ手順)
- [環境変数](#環境変数)
- [ログイン処理](#ログイン処理)
- [ユーザー作成](#ユーザー作成)
- [ロール（権限）](#ロール権限)
- [バッチ（スケジュール処理）](#バッチスケジュール処理)
- [API](#api)
- [ログ設計](#ログ設計)
- [テスト / 静的解析](#テスト--静的解析)
- [工夫した点](#工夫した点)

---

## 概要

| 項目 | 内容 |
| --- | --- |
| 種別 | ポートフォリオ（ユーザー管理システム） |
| フレームワーク | Laravel 12 / PHP 8.2 |
| フロント | Blade テンプレート + Vite + Tailwind CSS 4 |
| DB | MySQL |
| 認証 | Laravel 標準のセッション認証（`web` ガード） |
| ロール | 一般ユーザー / 管理者 の 2 種 |
| バッチ | Artisan コマンド + スケジューラ |
| API | REST（管理者ユーザー作成 API を API キー認証で提供） |

「誰が・いつ・何をしたか」を追えることを重視し、**ログイン履歴・変更履歴・アクセスログ**を
仕組みとして記録する設計にしています。

---

## 使用技術

- **PHP 8.2** / **Laravel 12**
- **Blade**（テンプレート）
- **Vite 7** / **Tailwind CSS 4**（`npm run dev` / `npm run build`）
- **MySQL**（本番想定）
- **PHPUnit 11**（テスト）
- **PHPStan + Larastan**（静的解析）/ **Laravel Pint**（コード整形）
- **GitHub Actions(プルリク時の自動テスト実行)

Composer / npm の主な依存は `composer.json`・`package.json` を参照してください。

---

## 主な機能

- ログイン / ログアウト（ログイン試行を成否問わず履歴に記録）
- 新規ユーザー登録（一般ロールで作成）
- ユーザー一覧（キーワード検索・ソート・ページネーション）
- ユーザー詳細 / プロフィール編集（本人 or 管理者。アバター画像アップロード対応）
- ユーザー一括削除（管理者のみ・チェックボックス選択・自分自身は削除不可）
- ログイン履歴の閲覧（一般＝自分のみ / 管理者＝全件＋絞り込み）
- 変更履歴の閲覧・登録・一括削除（登録/削除は管理者のみ）
- 一般ユーザー自動生成バッチ（毎日 02:00）
- 古いログファイルの自動削除バッチ（毎日 03:00）
- 管理者ユーザー作成 API（API キー認証）

---

## 画面一覧

| URL | 画面 | アクセス権 |
| --- | --- | --- |
| `/Login` | ログイン | ゲストのみ |
| `/register` | 新規登録 | ゲストのみ |
| `/users` | ユーザー一覧 | ログイン必須 |
| `/users/{id}` | ユーザー詳細 | ログイン必須 |
| `/users/{id}/edit` | プロフィール編集 | 本人 or 管理者 |
| `/login-histories` | ログイン履歴 | ログイン必須（表示範囲はロールで分岐） |
| `/change-histories` | 変更履歴一覧 | ログイン必須 |
| `/change-histories/create` | 変更履歴登録 | 管理者のみ |

> 編集権限は `UserPolicy`、変更履歴の登録権限は `ChangeHistoryPolicy` で制御しています。
> ロールにより表示範囲が変わる画面（ログイン履歴など）は Controller / Service 側で分岐します。

---

## アーキテクチャ / ディレクトリ構成

責務を分離するため、**Controller → Service → Repository** の 3 層構成を採用しています。

```
app/
├── Console/Commands/        # バッチ（Artisan コマンド）
│   ├── CreateGeneralUsersCommand.php
│   └── PurgeLogsCommand.php
├── Enums/
│   └── UserRole.php          # ロール定義（USER=1 / ADMIN=2）
├── Http/
│   ├── Controllers/          # リクエスト受付・レスポンス返却のみ
│   │   └── Api/              # API コントローラ
│   ├── Middleware/           # アクセスログ / 二重送信防止
│   └── Requests/             # バリデーション（FormRequest）
├── Models/                   # Eloquent モデル
├── Policies/                 # 認可（ユーザー / 変更履歴）
├── Providers/                # Policy・Blade ディレクティブ等の登録
├── Repositories/
│   ├── Contracts/            # リポジトリのインターフェース
│   └── Eloquent/             # Eloquent 実装
├── Services/                 # 業務ロジック（トランザクション境界）
└── Support/                  # 共通ヘルパー（アクセスログ・フォームトークン等）
```

**各層の役割**

- **Controller**：入力を受け取り Service を呼ぶ。業務ロジックは書かない。
- **Service**：業務ロジックとトランザクション管理。失敗時はログを残して例外を再送出。
- **Repository**：DB アクセスの抽象化。インターフェース経由で DI（`RepositoryServiceProvider` で束縛）。
- **FormRequest**：バリデーション＋認可（API キー照合もここ）。
- **Policy**：ロールに基づく認可。

---

## セットアップ手順

### 前提

- PHP 8.2 以上 / Composer / Node.js 18 以上
- MySQL

### 手順

```bash
# 1. 依存パッケージのインストール（初回は composer.json の setup スクリプトで一括）
composer install
cp .env.example .env        # Windows: copy .env.example .env
php artisan key:generate

# 2. マイグレーション
php artisan migrate

# 3. フロントのビルド
npm install
npm run build               # 開発中は npm run dev

# 4. 起動
php artisan serve
```

`composer setup` を使うと上記（install / key 生成 / migrate / npm install / build）を
まとめて実行できます。

### 一括開発起動

```bash
composer dev
```

サーバ・キュー・ログ（pail）・Vite を同時に起動します。

---

## 環境変数

主な設定値（`.env`）：

| 変数 | 用途 |
| --- | --- |
| `DB_CONNECTION` | `mysql` もしくは `sqlite` |
| `SESSION_DRIVER` | セッション保存先（既定 `database`） |
| `ADMIN_API_KEY` | 管理者ユーザー作成 API の API キー（**未設定だと API は常に 403**） |
| `LOG_CHANNEL` / `LOG_STACK` | ログ出力設定 |

> `ADMIN_API_KEY` は `config/api.php` 経由で読み込みます。
> コード側に既定値を持たせず、設定漏れを検知しやすくしています。

---

## ログイン処理

### 概要

Laravel 標準のセッション認証（`userid` + `password`）を使用します。
`email` ではなく独自の **`userid`** をログイン ID としている点が特徴です。

### 関連クラス

| クラス | 役割 |
| --- | --- |
| `LoginController` | ログイン画面表示・ログイン試行・ログアウト |
| `LoginRequest` | 入力バリデーション |
| `AuthService` | 認証試行と履歴記録 |
| `LoginHistory` | ログイン履歴モデル |

### 処理の流れ

1. `LoginRequest` で `userid` / `password` を必須チェック。
2. `AuthService::attempt()` が `Auth::attempt()` で認証。
3. **成否にかかわらず** `login_histories` に履歴を 1 件記録
   （`userid`・IP・User-Agent・成功フラグ・日時）。
   - 履歴記録は独立したトランザクションで行い、失敗しても**ログイン自体は失敗させない**（ログに残すのみ）。
4. 成功時は `session()->regenerate()` でセッション固定攻撃を防止し、ユーザー一覧へリダイレクト。

### セキュリティ対策

- **ブルートフォース対策**：ログイン POST に `throttle:5,1`（1 分 5 回）を適用。
- **セッション固定攻撃対策**：ログイン成功時に `session()->regenerate()`。
- **ログアウト**：`Auth::logout()` + `session()->invalidate()` + `regenerateToken()`。
- **リダイレクト先**：`intended()` は前セッションの他人ページへ飛ぶ恐れがあるため使わず、
  ログイン直後は必ず一覧へ送る。

---

## ユーザー作成

ユーザーが作成される経路は次の 3 つです。いずれも作成ロジックは `UserService` に集約しています。

| 経路 | 入口 | ロール |
| --- | --- | --- |
| 画面からの新規登録 | `RegisterController` → `UserService::register()` | 一般固定 |
| バッチによる自動生成 | `CreateGeneralUsersCommand` → `UserService::createWithRole()` | 一般 |
| API による作成 | `Api\AdminUserController` → `UserService::createWithRole()` | 管理者 |

### 新規登録（画面）

- `RegisterRequest` でバリデーション：`userid`（4〜30 文字・半角英数字/ハイフン/アンダースコア・重複不可）、
  `name`（最大 50 文字）、`password`（8 文字以上・確認用と一致）。
- `UserService::register()` がロールを **一般固定** で作成。
- **自動ログインは行わず**、ログイン画面へ誘導します。

### userid の自動採番

`UserService::generateUniqueUserid('user')` が `user1, user2, ...` のように
**未使用の連番**を採番します（既存と衝突したら次の番号へ進める）。
API では接頭辞 `admin`（`admin1, admin2, ...`）で採番します。

### パスワード

- モデルの `casts()` で `password => 'hashed'` を指定しており、保存時に自動ハッシュ化（bcrypt）。
- `password` は `$hidden`、`userid` / `name` / `role` / `avatar_path` は `$fillable` で適切に制御。

---

## ロール（権限）

### ロール定義

`App\Enums\UserRole` で PHP の Enum として管理しています。

| 値 | 名称 | 説明 |
| --- | --- | --- |
| `1` | 一般（`USER`） | 一般ユーザー |
| `2` | 管理者（`ADMIN`） | 管理者 |

`users.role` カラム（`tinyInteger`、既定値 `1`）に保存し、モデルで Enum にキャスト。
判定は `User::isAdmin()` に集約しています。

### 権限マトリクス

| 操作 | 一般ユーザー | 管理者 |
| --- | --- | --- |
| ユーザー一覧 / 詳細の閲覧 | ✅ | ✅ |
| 自分のプロフィール編集 | ✅ | ✅ |
| 他人のプロフィール編集 | ❌ | ✅ |
| ロールの変更 | ❌ | ✅ |
| ユーザーの一括削除 | ❌ | ✅ |
| 自分のログイン履歴の閲覧 | ✅（自分のみ） | ✅ |
| 全ユーザーのログイン履歴の閲覧 | ❌ | ✅ |
| 変更履歴の閲覧 | ✅ | ✅ |
| 変更履歴の登録 / 削除 | ❌ | ✅ |

### 実装方法

- **Policy**
  - `UserPolicy`：`update`（本人 or 管理者）・`changeRole`（管理者のみ）・`delete`（管理者のみ）。
  - `ChangeHistoryPolicy`：`create` / `delete`（管理者のみ）。
  - `AppServiceProvider` で明示的に登録し、自動発見に依存しない。
- **Gate**：Controller の `Gate::authorize()` で呼び出し（例：`UserController::edit()`）。
- **Service 層の安全策**：一括削除時は、存在しない ID と **操作者自身**を除外
  （自分のアカウントを消してログイン不能になるのを防ぐ）。

---

## バッチ（スケジュール処理）

Artisan コマンドとして実装し、`routes/console.php` でスケジュール登録しています。

| コマンド | 説明 | 実行タイミング |
| --- | --- | --- |
| `users:create-general` | 一般ユーザーを指定件数作成（既定 1 件） | 毎日 02:00 |
| `logs:purge` | 指定日数を経過したログファイルを削除（既定 120 日） | 毎日 03:00 |

### 1. 一般ユーザー作成バッチ（`users:create-general`）

```bash
php artisan users:create-general          # 1 件作成（既定）
php artisan users:create-general 5        # 5 件作成
```

- `user` + 連番で未使用の `userid` を自動採番。
- 既定パスワードは `password123`、表示名は「自動生成ユーザー{userid}」。
- 引数は 1 以上の整数のみ受け付け、不正時は終了コード `1`。

### 2. ログ削除バッチ（`logs:purge`）

```bash
php artisan logs:purge              # 120 日を経過したログを削除
php artisan logs:purge 30           # 30 日を経過したログを削除
php artisan logs:purge 30 --dry-run # 削除対象を表示するだけ（削除しない）
```

- `storage/logs` 配下の `laravel-YYYY-MM-DD.log` 等、**ファイル名に日付を含むもの**だけが対象。
- 日付が判定できないファイル（`laravel.log` 等）は誤削除防止のため対象外。
- `--dry-run` で対象確認のみ可能。

### スケジューラの有効化

サーバー側で **1 分ごとに** `php artisan schedule:run` を回す必要があります。

Windows のタスクスケジューラ登録例：

```
C:\xampp\php\php.exe C:\xampp\htdocs\portfolio\artisan schedule:run
```

確認用：

```bash
php artisan schedule:list   # 登録内容の確認
php artisan schedule:run    # 手動で 1 回実行
```

> スケジュールは `onOneServer()` と `withoutOverlapping()` を指定し、
> 複数サーバー運用時の二重実行や処理の重複を防いでいます。

### バッチのログ

バッチも HTTP と同様に **アクセスログ**（`App\Support\AccessLog`）へ 1 実行 = 1 レコード記録します。
CLI は HTTP ではないため自動記録されず、各コマンドから明示的に呼び出しています。

---

## API

現在提供しているのは **管理者ユーザー作成 API** の 1 本です。

### `POST /api/admin/users`

管理者ロール（`role = 2`）のユーザーを新規作成します。
この API では一般ユーザーは作成できず、**必ず管理者ロール**になります。

#### 認証・認可

- ログインセッションは**不要**。固定の **API キー**で認可します（`StoreAdminUserRequest::authorize()`）。
- API キーの受け渡し（優先順）：
  1. リクエストヘッダ `X-API-KEY`
  2. ボディ / クエリの `api_key`
- 実値は `.env` の `ADMIN_API_KEY`（`config/api.php` 経由）。`hash_equals()` で定数時間比較し、
  タイミング攻撃を回避しています。
- キー不一致・未設定は **403** を返します。

#### リクエスト例

```bash
curl -X POST http://localhost/api/admin/users \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "X-API-KEY: <ADMIN_API_KEY の値>" \
  -d '{"userid":"admin02","name":"管理者2","password":"password123"}'
```

`userid` を省略した場合は `admin` + 連番で自動採番されます。

#### レスポンス例（201 Created）

```json
{
  "data": {
    "id": 12,
    "userid": "admin02",
    "name": "管理者2",
    "role": 2,
    "role_label": "管理者",
    "created_at": "2026-01-01T00:00:00.000Z"
  }
}
```

#### ステータスコード

| コード | 意味 |
| --- | --- |
| 201 | 作成成功 |
| 403 | API キー不一致（未設定含む） |
| 422 | バリデーションエラー（userid 重複・文字数など） |

#### 実装上のポイント

- `routes/api.php` は `web` ミドルウェアグループに載せ、`bootstrap/app.php` で
  `api/*` を **CSRF 検証の対象外**に設定（フォームではないためトークンを持たない）。
- 認可は「ログイン」ではなく「API キー」で行うため `auth` ミドルウェアは付けていません。
  将来セッション認証が必要な API を足す場合は、そのルートに `->middleware('auth')` を付与します。

---

## ログ設計

「誰が・いつ・何をしたか」を追跡できるよう、主に 3 種類のログを運用します。

### 1. ログイン履歴（DB：`login_histories`）

- ログイン試行の**成否にかかわらず**記録。
- 記録項目：`user_id`・`userid`・`ip_address`・`user_agent`・`success`・`logged_in_at`。
- 退会（ユーザー削除）後も履歴は残す設計（`nullOnDelete`）。

### 2. 変更履歴（DB：`change_histories`）

- 管理者が登録する変更内容の記録。`body`（本文）と `registered_at`（登録日時）を保持。
- 登録者を `userid` にも控え、退会後も誰の記録か分かるようにしている。

### 3. アクセスログ（ファイル：`storage/logs/laravel-YYYY-MM-DD.log`）

- `LogAccess` ミドルウェアが **全 HTTP リクエスト**（Web / API）を 1 アクセス = 5 行で記録。
- バッチは `AccessLog::log()` を各コマンドから呼んで記録。
- SQL ログ（`QueryLogger`）と統合しており、アクセスに紐づくクエリとして読める配置にしている。
- 古いファイルは `logs:purge` バッチで自動削除。

出力イメージ：

```
***********************************************
2026/09/21 07:40:05
users/23/edit
users.id : 23
users.userid : admin_test
```

---

## テスト / 静的解析

```bash
# テスト
composer test           # または php artisan test

# 静的解析（PHPStan + Larastan）
vendor/bin/phpstan analyse

# コード整形（Laravel Pint）
vendor/bin/pint
# または
composer lint
```

モデルのプロパティには型アノテーションを付け、Larastan が Enum 比較を正しく判定できるようにしています。

---

## 工夫した点

- **責務分離**：Controller / Service / Repository / Policy / FormRequest を分け、テストしやすく変更に強い構成に。
- **ロール設計**：PHP Enum でロールを型安全に管理し、判定を `isAdmin()` に集約。
- **履歴の徹底**：ログイン試行・変更・アクセスを仕組みとして記録し、監査性を確保。
- **二重送信対策**：`PreventDuplicateSubmission` ミドルウェアでサーバー側でも連打を防止
  （JS のボタン無効化は突破され得るため、サーバー側が最終防衛線）。
- **セキュリティ**：ログインのレート制限・セッション再生成・API キーの定数時間比較など。
- **運用力**：スケジューラによる自動バッチ（ユーザー生成・ログローテーション）と `--dry-run` 対応。

---

## ライセンス

本リポジトリは就職活動用のポートフォリオとして個人制作したものです。
