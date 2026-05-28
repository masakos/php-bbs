# WSL を使った LAMP 環境構築手順

## 前提条件

- Windows 10 バージョン 2004 以降、または Windows 11
- 管理者権限のある Windows アカウント

---

## 1. WSL2 のインストール

PowerShell を **管理者として** 起動し、以下を実行する。

```powershell
wsl --install
```

> デフォルトで Ubuntu がインストールされる。再起動を求められたら再起動する。

再起動後、Ubuntu が自動起動するので、ユーザー名とパスワードを設定する。

WSL バージョンが 2 であることを確認。

```powershell
wsl --list --verbose
```

`VERSION` が `2` になっていれば OK。

---

## 2. Ubuntu パッケージの更新

Ubuntu ターミナルで実行。

```bash
sudo apt update && sudo apt upgrade -y
```

---

## 3. Apache のインストールと起動

```bash
sudo apt install -y apache2
sudo service apache2 start
```

動作確認：ブラウザで `http://localhost` を開き "Apache2 Default Page" が表示されれば OK。

> WSL は systemd が使えない環境があるため `service` コマンドを使う（`systemctl` の代替）。

---

## 4. MySQL のインストールと初期設定

```bash
sudo apt install -y mysql-server
sudo service mysql start
```

セキュリティ設定（パスワードポリシーや匿名ユーザーの削除など）。

```bash
sudo mysql_secure_installation
```

| 質問 | 推奨回答 |
|------|----------|
| VALIDATE PASSWORD component | `No`（開発環境なら任意） |
| Remove anonymous users | `Yes` |
| Disallow root login remotely | `Yes` |
| Remove test database | `Yes` |
| Reload privilege tables | `Yes` |

root でログインできるか確認。

```bash
sudo mysql -u root -p
```

---

## 5. PHP のインストール

```bash
sudo apt install -y php libapache2-mod-php php-mysql
```

バージョン確認。

```bash
php -v
```

---

## 6. Apache と PHP の連携確認

テスト用 PHP ファイルを作成。

```bash
sudo nano /var/www/html/info.php
```

以下を貼り付けて保存（Ctrl+O → Enter → Ctrl+X）。

```php
<?php phpinfo(); ?>
```

ブラウザで `http://localhost/info.php` を開き PHP 情報ページが表示されれば連携 OK。

確認後、セキュリティのためファイルを削除する。

```bash
sudo rm /var/www/html/info.php
```

---

## 7. phpMyAdmin のインストール（任意）

```bash
sudo apt install -y phpmyadmin
```

インストール中の設定：

| 質問 | 選択 |
|------|------|
| Web server to configure | `apache2`（スペースキーで選択 → Enter） |
| Configure database with dbconfig-common | `Yes` |
| phpMyAdmin の MySQL パスワード | 任意のパスワードを設定 |

ブラウザで `http://localhost/phpmyadmin` にアクセスして確認。

---

## 8. WSL 起動時にサービスを自動起動する設定（任意）

WSL は再起動のたびにサービスがリセットされるため、`~/.bashrc` に追記しておくと便利。

```bash
echo 'sudo service apache2 start > /dev/null 2>&1' >> ~/.bashrc
echo 'sudo service mysql start > /dev/null 2>&1' >> ~/.bashrc
```

sudo をパスワードなしで実行できるよう設定。

```bash
sudo visudo
```

末尾に以下を追記（`<username>` は自分のユーザー名）。

```
<username> ALL=(ALL) NOPASSWD: /usr/sbin/service apache2 *, /usr/sbin/service mysql *
```

---

## 9. プロジェクトファイルの配置

Web ドキュメントルートは `/var/www/html/`。

```bash
sudo mkdir /var/www/html/myapp
sudo chown $USER:$USER /var/www/html/myapp
```

Windows エクスプローラーからは `\\wsl$\Ubuntu\var\www\html\` でアクセス可能。

---

## バージョン確認まとめ

```bash
apache2 -v        # Apache バージョン
mysql --version   # MySQL バージョン
php -v            # PHP バージョン
```

---

## トラブルシューティング

| 症状 | 対処 |
|------|------|
| `http://localhost` に繋がらない | `sudo service apache2 status` でエラーを確認 |
| MySQL に接続できない | `sudo service mysql status` → `sudo service mysql restart` |
| ポート 80 が使用中 | Windows 側の IIS やほかのサービスを停止する |
| `sudo` でパスワードを毎回聞かれる | visudo で NOPASSWD を設定（手順 8 参照） |
