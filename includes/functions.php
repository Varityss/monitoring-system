<?php

require_once __DIR__ . '/../config/app.php';

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function availableLanguages(): array
{
    return [
        'ru' => 'RU',
        'kk' => 'KZ',
    ];
}

function currentLanguage(): string
{
    if (isset($_GET['lang']) && array_key_exists($_GET['lang'], availableLanguages())) {
        $_SESSION['lang'] = $_GET['lang'];
        setcookie('lang', $_GET['lang'], time() + 60 * 60 * 24 * 365, '/');
    }

    if (isset($_SESSION['lang']) && array_key_exists($_SESSION['lang'], availableLanguages())) {
        return $_SESSION['lang'];
    }

    if (isset($_COOKIE['lang']) && array_key_exists($_COOKIE['lang'], availableLanguages())) {
        $_SESSION['lang'] = $_COOKIE['lang'];
        return $_COOKIE['lang'];
    }

    return 'ru';
}

function translations(): array
{
    return [
        'ru' => [
            'app.name' => 'Monitoring System',
            'app.subtitle' => 'Инвентаризация и выдача оборудования',
            'nav.dashboard' => 'Панель',
            'nav.equipment' => 'Оборудование',
            'nav.requests' => 'Заявки',
            'nav.return' => 'Возврат',
            'nav.users' => 'Пользователи',
            'nav.home' => 'Главная',
            'nav.login' => 'Вход',
            'nav.logout' => 'Выход',
            'teacher.requests' => 'Мои заявки',
            'teacher.new_request' => 'Подать заявку',
            'teacher.empty' => 'Заявок пока нет',
            'teacher.empty_hint' => 'Создайте первую заявку на оборудование.',
            'teacher.request_console' => 'Консоль заявок',
            'teacher.request_count' => 'Количество',
            'teacher.cabinet' => 'Кабинет',
            'teacher.location' => 'Локация',
            'teacher.period' => 'Период',
            'teacher.equipment' => 'Оборудование',
            'teacher.laptops' => 'ноутбуков',
            'request.new' => 'Новая заявка',
            'request.type' => 'Тип заявки',
            'request.selection' => 'Подбор оборудования',
            'request.select_equipment' => 'Выберите оборудование',
            'request.lesson' => 'Учебное занятие',
            'request.work' => 'Рабочее использование',
            'request.competition' => 'Соревнование',
            'request.home' => 'Временная выдача домой',
            'request.auto' => 'Любые свободные ноутбуки',
            'request.manual' => 'Выбрать конкретные ноутбуки',
            'request.lesson_date' => 'Дата занятия',
            'request.start' => 'Начало',
            'request.end' => 'Конец',
            'request.issue_from' => 'Выдача',
            'request.issue_until' => 'Возврат',
            'request.purpose' => 'Причина / комментарий',
            'request.submit' => 'Отправить заявку',
            'request.equipment_format' => 'Инв. номер / модель / кабинет',
            'error.count_required' => 'Укажите количество оборудования',
            'error.bad_date' => 'Неверная дата',
            'error.past_time' => 'Нельзя выбрать прошедшую дату или время',
            'error.end_after_start' => 'Время окончания должно быть позже времени начала',
            'error.manual_count' => 'Количество выбранного оборудования должно совпадать с заявкой',
            'error.equipment_unavailable' => 'Некоторое выбранное оборудование уже недоступно',
            'status.pending' => 'Ожидание',
            'status.active' => 'Активна',
            'status.overdue' => 'Просрочена',
            'status.returned' => 'Завершена',
            'status.rejected' => 'Отклонена',
            'lang.label' => 'Язык',
            'login.title' => 'Авторизация',
            'login.login' => 'Логин',
            'login.password' => 'Пароль',
            'login.submit' => 'Войти',
            'login.inactive' => 'Аккаунт деактивирован',
            'login.invalid' => 'Неверный логин или пароль',
            'home.title' => 'Система учета оборудования',
            'home.subtitle' => 'Управление ноутбуками, заявками и QR-учетом в одном рабочем контуре.',
            'home.login' => 'Войти в систему',
            'home.equipment.title' => 'Оборудование',
            'home.equipment.text' => 'Парк техники, статусы, кабинеты и история выдачи.',
            'home.requests.title' => 'Заявки',
            'home.requests.text' => 'Подача, согласование, выдача и возврат оборудования.',
            'home.qr.title' => 'QR учет',
            'home.qr.text' => 'Сканирование QR-кодов для поиска, выдачи и возврата.',
            'home.panel.registry' => 'Учет',
            'home.panel.active' => 'Активен',
            'home.panel.scan' => 'QR-сканер',
            'home.panel.ready' => 'Готов',
            'home.panel.requests' => 'Заявки',
            'home.panel.work' => 'В работе',
        ],
        'kk' => [
            'app.name' => 'Monitoring System',
            'app.subtitle' => 'Жабдықты есепке алу және беру',
            'nav.dashboard' => 'Бақылау',
            'nav.equipment' => 'Жабдық',
            'nav.requests' => 'Өтінімдер',
            'nav.return' => 'Қайтару',
            'nav.users' => 'Пайдаланушылар',
            'nav.home' => 'Басты бет',
            'nav.login' => 'Кіру',
            'nav.logout' => 'Шығу',
            'teacher.requests' => 'Менің өтінімдерім',
            'teacher.new_request' => 'Өтінім беру',
            'teacher.empty' => 'Әзірге өтінім жоқ',
            'teacher.empty_hint' => 'Жабдыққа алғашқы өтінімді жасаңыз.',
            'teacher.request_console' => 'Өтінімдер консолі',
            'teacher.request_count' => 'Саны',
            'teacher.cabinet' => 'Кабинет',
            'teacher.location' => 'Орналасқан жері',
            'teacher.period' => 'Кезең',
            'teacher.equipment' => 'Жабдық',
            'teacher.laptops' => 'ноутбук',
            'request.new' => 'Жаңа өтінім',
            'request.type' => 'Өтінім түрі',
            'request.selection' => 'Жабдықты таңдау',
            'request.select_equipment' => 'Жабдықты таңдаңыз',
            'request.lesson' => 'Оқу сабағы',
            'request.work' => 'Жұмыс үшін пайдалану',
            'request.competition' => 'Жарыс',
            'request.home' => 'Үйге уақытша беру',
            'request.auto' => 'Кез келген бос ноутбуктер',
            'request.manual' => 'Нақты ноутбуктерді таңдау',
            'request.lesson_date' => 'Сабақ күні',
            'request.start' => 'Басталуы',
            'request.end' => 'Аяқталуы',
            'request.issue_from' => 'Берілетін уақыт',
            'request.issue_until' => 'Қайтару уақыты',
            'request.purpose' => 'Себебі / түсініктеме',
            'request.submit' => 'Өтінімді жіберу',
            'request.equipment_format' => 'Инв. нөмір / модель / кабинет',
            'error.count_required' => 'Жабдық санын көрсетіңіз',
            'error.bad_date' => 'Күн дұрыс емес',
            'error.past_time' => 'Өткен күнді немесе уақытты таңдауға болмайды',
            'error.end_after_start' => 'Аяқталу уақыты басталу уақытынан кейін болуы керек',
            'error.manual_count' => 'Таңдалған жабдық саны өтінімдегі санмен сәйкес болуы керек',
            'error.equipment_unavailable' => 'Таңдалған жабдықтың бір бөлігі қолжетімсіз',
            'status.pending' => 'Күтуде',
            'status.active' => 'Белсенді',
            'status.overdue' => 'Мерзімі өтті',
            'status.returned' => 'Аяқталды',
            'status.rejected' => 'Қабылданбады',
            'lang.label' => 'Тіл',
            'login.title' => 'Авторизация',
            'login.login' => 'Логин',
            'login.password' => 'Құпиясөз',
            'login.submit' => 'Кіру',
            'login.inactive' => 'Аккаунт өшірілген',
            'login.invalid' => 'Логин немесе құпиясөз дұрыс емес',
            'home.title' => 'Жабдықты есепке алу жүйесі',
            'home.subtitle' => 'Ноутбуктерді, өтінімдерді және QR есебін бір жұмыс контурында басқару.',
            'home.login' => 'Жүйеге кіру',
            'home.equipment.title' => 'Жабдық',
            'home.equipment.text' => 'Техника қоры, статустар, кабинеттер және беру тарихы.',
            'home.requests.title' => 'Өтінімдер',
            'home.requests.text' => 'Өтінім беру, келісу, жабдықты беру және қайтару.',
            'home.qr.title' => 'QR есеп',
            'home.qr.text' => 'Іздеу, беру және қайтару үшін QR-кодтарды сканерлеу.',
            'home.panel.registry' => 'Есеп',
            'home.panel.active' => 'Белсенді',
            'home.panel.scan' => 'QR-сканер',
            'home.panel.ready' => 'Дайын',
            'home.panel.requests' => 'Өтінімдер',
            'home.panel.work' => 'Жұмыста',
        ],
    ];
}

function t(string $key): string
{
    $lang = currentLanguage();
    $translations = translations();

    return $translations[$lang][$key]
        ?? $translations['ru'][$key]
        ?? $key;
}

function languageUrl(string $lang): string
{
    $params = $_GET;
    $params['lang'] = $lang;
    $query = http_build_query($params);
    $path = strtok($_SERVER['REQUEST_URI'] ?? '', '?') ?: '';

    return $path . ($query ? '?' . $query : '');
}

function languageSwitcher(): string
{
    $current = currentLanguage();
    $html = '<div class="language-switcher" aria-label="' . e(t('lang.label')) . '">';

    foreach (availableLanguages() as $code => $label) {
        $class = $code === $current ? 'active' : '';
        $html .= '<a class="' . $class . '" href="' . e(languageUrl($code)) . '">' . e($label) . '</a>';
    }

    return $html . '</div>';
}

function url(string $path = ''): string
{
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}

function assetUrl(string $path): string
{
    $file = BASE_PATH . '/' . ltrim($path, '/');
    $version = is_file($file) ? filemtime($file) : time();

    return url($path) . '?v=' . $version;
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function verifyCsrfToken(): void
{
    $token = $_POST['csrf_token'] ?? '';

    if (!is_string($token) || !hash_equals(csrfToken(), $token)) {
        http_response_code(403);
        die('Invalid CSRF token');
    }
}

function requirePostRequest(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        die('Method not allowed');
    }
}

function currentUserId(): ?int
{
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

function equipmentScanCandidates(string $code): array
{
    $code = trim($code);

    if ($code === '') {
        return [];
    }

    $candidates = [$code];
    $query = parse_url($code, PHP_URL_QUERY);

    if (is_string($query) && $query !== '') {
        parse_str(str_replace('&amp;', '&', $query), $params);

        if (isset($params['token']) && is_scalar($params['token'])) {
            $candidates[] = trim((string)$params['token']);
        }
    }

    if (preg_match('/(?:^|[?&])token=([^&#]+)/', $code, $matches)) {
        $candidates[] = trim(urldecode($matches[1]));
    }

    return array_values(array_unique(array_filter(
        $candidates,
        static fn ($candidate) => $candidate !== ''
    )));
}

function logActivity(
    PDO $pdo,
    string $action,
    string $entityType,
    ?int $entityId = null,
    ?string $description = null,
    ?int $userId = null
): void {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (
                user_id,
                action,
                entity_type,
                entity_id,
                description
            )
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $userId ?? currentUserId(),
            $action,
            $entityType,
            $entityId,
            $description
        ]);
    } catch (Throwable $e) {
        // Logging must never break the main workflow.
    }
}

function equipmentStatusLabel(?string $status): string
{
    $labels = [
        'available' => 'Доступно',
        'reserved' => 'Зарезервировано',
        'issued' => 'Выдано',
        'repair' => 'На ремонте',
        'written_off' => 'Списано',
    ];

    return $labels[$status] ?? 'Неизвестно';
}

function requestStatusLabel(?string $status): string
{
    $labels = [
        'pending' => 'Ожидает рассмотрения',
        'approved' => 'Одобрена',
        'rejected' => 'Отклонена',
        'issued' => 'Оборудование выдано',
        'returned' => 'Оборудование возвращено',
        'cancelled' => 'Отменена',
        'overdue' => 'Просрочена',
    ];

    return $labels[$status] ?? 'Неизвестно';
}

function requestEndTimestamp(array $request): ?int
{
    if (!empty($request['issued_until'])) {
        $timestamp = strtotime($request['issued_until']);
        return $timestamp === false ? null : $timestamp;
    }

    if (!empty($request['lesson_date']) && !empty($request['end_time'])) {
        $timestamp = strtotime($request['lesson_date'] . ' ' . $request['end_time']);
        return $timestamp === false ? null : $timestamp;
    }

    return null;
}
