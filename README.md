# フリマアプリ

## 環境構築

### Docker ビルド

1.  git clone <リポジトリのリンク>
2.  docker-compose up -d --build

＊ MySQL は OS によって起動しない場合があります。その場合は、docker-compose.yml ファイルを編集し、それぞれの PC に合わせて調整してください。

### Laravel 環境構築

1.  docker-compose exec php bash
2.  composer install
3.  .env.example をコピーして.env ファイルを作成し、環境変数を変更<br>
    ※.env ファイルの DB_DATABASE、DB_USERNAME、DB_PASSWORD の値を docker-compose.yml に記載の値に変更
4.  php artisan key:generate
5.  php artisan migrate
6.  php artisan db:seed<br>
    ※ログインの際必要なデータは database\seeders\UsersTableSeeder.php に記載
7.  php artisan storage:link

## 使用技術

-   PHP: 8.4.1
-   Laravel: 8.83.8
-   MySQL: 8.0.26
-   mailhog

## 複数ユーザーの同時ログインについて

※Google Chrome 等の同じブラウザで、複数ユーザーを同時にログインして別タブでページを確認する場合、最後にログインしたユーザーの状態にすべてのタブが切り替わる ← 通常のブラウザではセッション情報（ログイン状態）が共有されるため、異なるユーザーで同時にログインして動作を確認する際(取引チャット、取引評価等)は、下記の方法等を利用してください。

① シークレットウィンドウ（プライベートブラウジング）を利用する

-   Chrome では「Ctrl+Shift+N」でシークレットウィンドウを開き、そこから別のユーザーでログインできます。<br>通常ウィンドウとシークレットウィンドウでそれぞれ別のユーザーとして動作確認が可能です。

② 別のブラウザを利用する

-   例えば、Google Chrome と Microsoft Edge、Firefox など、異なるブラウザを使うことでそれぞれ独立したログイン状態を保持できます。<br>通常ウィンドウ + シークレットウィンドウ + 別ブラウザのシークレットウィンドウ、などを併用すると 3 ユーザーの同時ログイン確認が可能です。

注意:
Google Chrome の「新しいプロファイル」機能を利用する方法もありますが、上記の「シークレットウィンドウ」や「別ブラウザ」の方が手軽です。

## 機能補足

① 商品購入時の所作

-   購入機能について基本要件と応用要件を両立させるため、商品購入ページにて<br>コンビニ払いを選択して購入する → そのまま Sold 処理が実行される<br>カード払いを選択して購入する →stripe の決済画面に移動する
-   stripe の決済画面に移動できるようにするため.env ファイルの<br>
    STRIPE_PUBLIC=your-public-api-key-here<br>
    STRIPE_SECRET_KEY=your-secret-api-key-here<br>
    に Stripe のダッシュボードから API キーを取得して記述する
-   stripe 決済画面においてテスト環境で支払いを実行するため仮想の情報を入力して支払うをクリックする<br>
    例：カード番号 4242 4242 4242 4242
    有効期限 12/30
    セキュリティコード 123<br>
    メールアドレス sample@stripe.com
    カード保有者の名前 Test User

② 商品購入～取引完了におけるプロフィール画面における商品表示の流れ<br>

1.  商品購入後(出品者購入者いずれの場合でも)取引中の商品に追加表示される<br>
2.  出品者購入者双方評価を送信して取引完了後<br> -購入者側 → 取引中の商品から購入した商品に移動<br> -出品者側 → 取引中の商品から表示が消える

## テストコード補足

-   会員登録機能(全ての項目が入力されている場合、会員情報が登録され、ログイン画面に遷移される)<br>
    → 応用要件に合わせてメール認証画面へ遷移されるように設定
-   マイリスト一覧取得(未認証の場合は何も表示されない)<br>
    → 機能要件に合わせて未認証の場合ログイン画面へ遷移されるように設定
-   コメント送信機能(ログイン前のユーザーはコメントを送信できない)<br>
    → ログインしてコメントするというボタンからログイン画面へ遷移されるように設定(コメント欄は未表示)

## URL

-   開発環境: [http://localhost/](http://localhost/)
-   MailHog: [http://localhost:8025](http://localhost:8025)
-   phpMyAdmin: [http://localhost:8080/](http://localhost:8080/)

## ER 図

![フリマアプリER図](https://github.com/user-attachments/assets/4457baff-a85b-4c8f-9a05-5f813c4e8127)
