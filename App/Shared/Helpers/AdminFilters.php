<?php

namespace App\Shared\Helpers;

class AdminFilters
{
    public static function strParam(string $key): string
    {
        return isset($_GET[$key]) ? trim((string)$_GET[$key]) : '';
    }

    public static function intParam(string $key, int $default = 0): int
    {
        if (!isset($_GET[$key])) {
            return $default;
        }
        $value = filter_var($_GET[$key], FILTER_VALIDATE_INT);
        return $value !== false && $value !== null ? max(0, (int)$value) : $default;
    }

    public static function currentUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = strtok($uri, '?') ?: '/';
        return $path;
    }

    /**
     * Build a query string from the current $_GET with the given overrides
     * applied, so filters survive when navigating pages.
     */
    public static function preserve(array $overrides = [], array $except = []): string
    {
        $params = $_GET;
        foreach ($except as $key) {
            unset($params[$key]);
        }
        foreach ($overrides as $key => $value) {
            if ($value === '' || $value === null || $value === false) {
                unset($params[$key]);
            } else {
                $params[$key] = (string)$value;
            }
        }
        return $params ? '?' . http_build_query($params) : '';
    }

    public static function paginate(int $total, int $perPage = 10): array
    {
        $perPage = max(1, $perPage);
        $totalPages = max(1, (int)ceil($total / $perPage));
        $page = self::intParam('page', 1);
        if ($page < 1) {
            $page = 1;
        }
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        return [
            'total' => $total,
            'perPage' => $perPage,
            'page' => $page,
            'totalPages' => $totalPages,
            'offset' => ($page - 1) * $perPage,
            'start' => $total === 0 ? 0 : (($page - 1) * $perPage + 1),
            'end' => min($total, $page * $perPage),
        ];
    }

    public static function paginationHtml(array $p): string
    {
        if ($p['totalPages'] <= 1) {
            return '';
        }

        $base = self::currentUri();
        $page = (int)$p['page'];
        $totalPages = (int)$p['totalPages'];

        $btn = 'inline-flex items-center justify-center min-w-[34px] h-9 px-3 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-600 hover:border-red-200 hover:text-red-600 hover:bg-red-50 transition-all active:scale-95';
        $btnActive = 'inline-flex items-center justify-center min-w-[34px] h-9 px-3 rounded-xl bg-red-600 border border-red-600 text-white text-xs font-extrabold shadow-sm shadow-red-600/20';
        $btnDisabled = 'inline-flex items-center justify-center min-w-[34px] h-9 px-3 rounded-xl border border-slate-100 bg-slate-50 text-xs font-bold text-slate-300 cursor-not-allowed';

        $html = '<nav class="flex items-center justify-between gap-3 flex-wrap" aria-label="Pagination">';

        $html .= '<p class="text-xs font-semibold text-slate-400">Showing <span class="text-slate-700 font-bold">' . $p['start'] . '–' . $p['end'] . '</span> of <span class="text-slate-700 font-bold">' . $p['total'] . '</span> records</p>';

        $html .= '<div class="flex items-center gap-1.5">';

        if ($page > 1) {
            $html .= '<a href="' . htmlspecialchars($base . self::preserve(['page' => $page - 1])) . '" class="' . $btn . '"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg></a>';
        } else {
            $html .= '<span class="' . $btnDisabled . '"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg></span>';
        }

        $start = max(1, $page - 2);
        $end = min($totalPages, $page + 2);
        if ($start > 1) {
            $html .= '<a href="' . htmlspecialchars($base . self::preserve(['page' => 1])) . '" class="' . $btn . '">1</a>';
            if ($start > 2) {
                $html .= '<span class="px-1 text-slate-300 text-xs font-bold select-none">…</span>';
            }
        }
        for ($i = $start; $i <= $end; $i++) {
            if ($i === $page) {
                $html .= '<span class="' . $btnActive . '">' . $i . '</span>';
            } else {
                $html .= '<a href="' . htmlspecialchars($base . self::preserve(['page' => $i])) . '" class="' . $btn . '">' . $i . '</a>';
            }
        }
        if ($end < $totalPages) {
            if ($end < $totalPages - 1) {
                $html .= '<span class="px-1 text-slate-300 text-xs font-bold select-none">…</span>';
            }
            $html .= '<a href="' . htmlspecialchars($base . self::preserve(['page' => $totalPages])) . '" class="' . $btn . '">' . $totalPages . '</a>';
        }

        if ($page < $totalPages) {
            $html .= '<a href="' . htmlspecialchars($base . self::preserve(['page' => $page + 1])) . '" class="' . $btn . '"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg></a>';
        } else {
            $html .= '<span class="' . $btnDisabled . '"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg></span>';
        }

        $html .= '</div></nav>';

        return $html;
    }

    /**
     * Fetch BLOOD_GROUP options from master_data as [code => label].
     */
    public static function bloodGroups(\PDO $db): array
    {
        $stmt = $db->prepare("SELECT code, label FROM master_data WHERE category = 'BLOOD_GROUP' ORDER BY id");
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_KEY_PAIR) ?: [];
    }

    /**
     * Fetch REQUEST_STATUS options from master_data as [id => label].
     */
    public static function requestStatuses(\PDO $db): array
    {
        $stmt = $db->prepare("SELECT id, label FROM master_data WHERE category = 'REQUEST_STATUS' ORDER BY id");
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_KEY_PAIR) ?: [];
    }
}
