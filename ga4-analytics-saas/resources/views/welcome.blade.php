<!DOCTYPE html>
<html lang="ja" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GA4 Analytics SaaS - データ分析を簡単に</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
    <!-- ナビゲーションバー -->
    <div class="navbar bg-base-100 shadow-lg">
        <div class="navbar-start">
            <div class="dropdown">
                <div tabindex="0" role="button" class="btn btn-ghost lg:hidden">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h8m-8 6h16" />
                    </svg>
                </div>
                <ul tabindex="0"
                    class="menu menu-sm dropdown-content mt-3 z-[1] p-2 shadow bg-base-100 rounded-box w-52">
                    <li><a href="#features">機能</a></li>
                    <li><a href="#pricing">料金プラン</a></li>
                    <li><a href="#faq">よくある質問</a></li>
                </ul>
            </div>
            <a class="btn btn-ghost text-xl">GA4 Analytics SaaS</a>
        </div>
        <div class="navbar-center hidden lg:flex">
            <ul class="menu menu-horizontal px-1">
                <li><a href="#features">機能</a></li>
                <li><a href="#pricing">料金プラン</a></li>
                <li><a href="#faq">よくある質問</a></li>
            </ul>
        </div>
        <div class="navbar-end">
            @if (Route::has('login'))
                @auth
                    <a href="{{ url('/dashboard') }}" class="btn btn-primary">ダッシュボード</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-ghost">ログイン</a>
                    <a href="{{ route('register') }}" class="btn btn-primary">無料で始める</a>
                @endauth
            @endif
        </div>
    </div>

    <!-- ヒーローセクション -->
    <div class="hero min-h-[80vh] bg-base-200">
        <div class="hero-content text-center">
            <div class="max-w-3xl">
                <h1 class="text-5xl font-bold mb-8">データ分析を、もっと簡単に。</h1>
                <p class="text-xl mb-8">
                    Google Analytics 4のデータを自動で分析し、わかりやすいレポートを生成。
                    ビジネスの意思決定を、データドリブンに。
                </p>
                <a href="{{ route('register') }}" class="btn btn-primary btn-lg">無料で始める</a>
            </div>
        </div>
    </div>

    <!-- 機能セクション -->
    <div id="features" class="py-20 bg-base-100">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-12">主な機能</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="card bg-base-200">
                    <div class="card-body">
                        <h3 class="card-title">自動レポート生成</h3>
                        <p>GA4のデータを自動で分析し、わかりやすいレポートを生成します。</p>
                    </div>
                </div>
                <div class="card bg-base-200">
                    <div class="card-body">
                        <h3 class="card-title">AIによる分析</h3>
                        <p>AIがデータを分析し、重要なインサイトを自動で抽出します。</p>
                    </div>
                </div>
                <div class="card bg-base-200">
                    <div class="card-body">
                        <h3 class="card-title">カスタマイズ可能</h3>
                        <p>ビジネスの目的に合わせて、レポートをカスタマイズできます。</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 料金プラン -->
    <div id="pricing" class="py-20 bg-base-200">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-12">料金プラン</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="card bg-base-100">
                    <div class="card-body">
                        <h3 class="card-title">スターター</h3>
                        <p class="text-3xl font-bold">¥0<span class="text-lg font-normal">/月</span></p>
                        <ul class="space-y-2 mt-4">
                            <li>✓ 基本的なレポート生成</li>
                            <li>✓ 1つのWebサイト</li>
                            <li>✓ 月次レポート</li>
                        </ul>
                        <div class="card-actions justify-end mt-4">
                            <a href="{{ route('register') }}" class="btn btn-primary">無料で始める</a>
                        </div>
                    </div>
                </div>
                <div class="card bg-primary text-primary-content">
                    <div class="card-body">
                        <h3 class="card-title">プロフェッショナル</h3>
                        <p class="text-3xl font-bold">¥9,800<span class="text-lg font-normal">/月</span></p>
                        <ul class="space-y-2 mt-4">
                            <li>✓ 高度なレポート生成</li>
                            <li>✓ 最大5つのWebサイト</li>
                            <li>✓ 週次レポート</li>
                            <li>✓ AI分析機能</li>
                        </ul>
                        <div class="card-actions justify-end mt-4">
                            <a href="{{ route('register') }}" class="btn btn-secondary">今すぐ始める</a>
                        </div>
                    </div>
                </div>
                <div class="card bg-base-100">
                    <div class="card-body">
                        <h3 class="card-title">エンタープライズ</h3>
                        <p class="text-3xl font-bold">¥29,800<span class="text-lg font-normal">/月</span></p>
                        <ul class="space-y-2 mt-4">
                            <li>✓ カスタムレポート生成</li>
                            <li>✓ 無制限のWebサイト</li>
                            <li>✓ 日次レポート</li>
                            <li>✓ 優先サポート</li>
                        </ul>
                        <div class="card-actions justify-end mt-4">
                            <a href="{{ route('register') }}" class="btn btn-primary">お問い合わせ</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FAQ -->
    <div id="faq" class="py-20 bg-base-100">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-12">よくある質問</h2>
            <div class="max-w-3xl mx-auto space-y-4">
                <div class="collapse collapse-arrow bg-base-200">
                    <input type="radio" name="faq-accordion" checked="checked" />
                    <div class="collapse-title text-xl font-medium">
                        どのようなデータを分析できますか？
                    </div>
                    <div class="collapse-content">
                        <p>Google Analytics 4のデータを分析し、ユーザー行動、コンバージョン、トラフィックソースなどの重要な指標を自動で分析します。</p>
                    </div>
                </div>
                <div class="collapse collapse-arrow bg-base-200">
                    <input type="radio" name="faq-accordion" />
                    <div class="collapse-title text-xl font-medium">
                        技術的な知識は必要ですか？
                    </div>
                    <div class="collapse-content">
                        <p>いいえ、技術的な知識は必要ありません。簡単な設定だけで、自動的にデータ分析とレポート生成が行われます。</p>
                    </div>
                </div>
                <div class="collapse collapse-arrow bg-base-200">
                    <input type="radio" name="faq-accordion" />
                    <div class="collapse-title text-xl font-medium">
                        無料プランで試せますか？
                    </div>
                    <div class="collapse-content">
                        <p>はい、無料プランで基本的な機能を試すことができます。クレジットカード情報は不要です。</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA -->
    <div class="py-20 bg-primary text-primary-content">
        <div class="container mx-auto px-4 text-center">
            <h2 class="text-3xl font-bold mb-8">今すぐ始めましょう</h2>
            <p class="text-xl mb-8">データ分析を自動化して、ビジネスの成長を加速させましょう。</p>
            <a href="{{ route('register') }}" class="btn btn-secondary btn-lg">無料で始める</a>
        </div>
    </div>

    <!-- フッター -->
    <footer class="footer p-10 bg-neutral text-neutral-content">
        <div>
            <span class="footer-title">サービス</span>
            <a class="link link-hover">機能</a>
            <a class="link link-hover">料金プラン</a>
            <a class="link link-hover">よくある質問</a>
        </div>
        <div>
            <span class="footer-title">会社</span>
            <a class="link link-hover">会社概要</a>
            <a class="link link-hover">お問い合わせ</a>
            <a class="link link-hover">プライバシーポリシー</a>
        </div>
        <div>
            <span class="footer-title">ソーシャル</span>
            <a class="link link-hover">Twitter</a>
            <a class="link link-hover">Facebook</a>
            <a class="link link-hover">LinkedIn</a>
        </div>
    </footer>
</body>

</html>
