<?php declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use function App\Core\require_auth;
use function App\Core\require_permission;
use function App\Core\set_locale;
use function App\Core\get_locale;
use function App\Core\get_available_locales;
use function App\Core\redirect;
use function App\Core\base_url;
use function App\Core\flash_set;

final class TranslationsController extends Controller
{
    public function index(): void
    {
        require_auth();
        require_permission('settings.view');
        
        // Get all translation keys from English file for management
        $englishTranslations = include __DIR__ . '/../lang/en.php';
        $arabicTranslations = include __DIR__ . '/../lang/ar.php';
        
        // Translation files are already flat with dot notation keys
        $translationKeys = $englishTranslations;
        $arabicTranslationsFlat = $arabicTranslations;
        
        $this->view('translations/index', [
            'page_title' => 'Translation Management',
            'current_locale' => get_locale(),
            'available_locales' => get_available_locales(),
            'translation_keys' => $translationKeys,
            'arabic_translations' => $arabicTranslationsFlat,
            'stats' => [
                'total_keys' => count($translationKeys),
                'translated_keys' => count(array_filter($arabicTranslationsFlat)),
                'missing_keys' => count($translationKeys) - count(array_filter($arabicTranslationsFlat))
            ]
        ]);
    }
    
    public function switchLocale(): void
    {
        $requestedLocale = trim((string)($_GET['lang'] ?? ''));
        $availableLocales = array_keys(get_available_locales());
        
        // Validate locale
        if (in_array($requestedLocale, $availableLocales)) {
            set_locale($requestedLocale);
            flash_set('success', 'Language changed successfully');
        } else {
            flash_set('error', 'Invalid language selected');
        }
        
        // Redirect to previous page or home
        $referer = $_SERVER['HTTP_REFERER'] ?? base_url('/');
        
        // Clean the referer URL to avoid locale parameter loops
        $referer = preg_replace('/[?&]lang=[^&]*/', '', $referer);
        
        redirect($referer);
    }
    
    /**
     * Flatten a multi-dimensional array with dot notation keys
     */
    private function flattenArray(array $array, string $prefix = ''): array
    {
        $result = [];
        
        foreach ($array as $key => $value) {
            $newKey = $prefix ? "{$prefix}.{$key}" : $key;
            
            if (is_array($value)) {
                $result = array_merge($result, $this->flattenArray($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }
        
        return $result;
    }
}

