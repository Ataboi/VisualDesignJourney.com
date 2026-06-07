<?php
require_once __DIR__ . '/includes/functions.php';

$lang = vdj_current_language();

$aboutCopy = [
    'en' => [
        'page_title' => 'About — Visual Design Journey',
        'meta' => 'Visual Design Journey is an independent visual design archive and discovery platform for moodboards, design movements, typography, color, branding, and curated creative references.',
        'eyebrow' => 'About the platform',
        'title' => 'Visual Design Journey',
        'intro' => 'Visual Design Journey is an independent design inspiration and visual culture platform for graphic designers, students, curators, art directors, brand builders, and curious visual thinkers.',
        'updated' => 'Last updated',
        'toc' => 'On this page',
        'toc_items' => [
            'mission' => ['Mission', 'fa-compass'],
            'platform' => ['What the platform includes', 'fa-layer-group'],
            'editorial' => ['Editorial approach', 'fa-pen-nib'],
            'community' => ['Community and submissions', 'fa-users'],
            'funding' => ['Funding and partnerships', 'fa-handshake'],
            'roadmap' => ['Roadmap', 'fa-route'],
            'contact' => ['Contact', 'fa-envelope'],
        ],
        'stats' => [
            ['Boards', 'Curated visual collections for style, reference, and research.'],
            ['Archive', 'Design movements, visual traits, timelines, and related references.'],
            ['Vibe', 'Aesthetic discovery through moods, typography, color, and style.'],
        ],
        'sections' => [
            'mission' => [
                'title' => 'Mission',
                'icon' => 'fa-compass',
                'body' => [
                    'The goal of Visual Design Journey is to make visual culture easier to browse, compare, learn from, and cite. Instead of treating inspiration as an endless feed, the platform organizes references into boards, movements, vibes, articles, color palettes, and curator profiles.',
                    'The site is built for people who need context around visual work: what style it belongs to, what historical movement it connects to, what typography or palette it suggests, and how it can support a real creative project.',
                ],
            ],
            'platform' => [
                'title' => 'What the platform includes',
                'icon' => 'fa-layer-group',
                'body' => [
                    'Visual Design Journey combines several connected discovery areas. Boards collect visual references around a theme or project. Curators organize references into public profiles. Trends surface what styles and boards are rising. Vibe helps users explore aesthetics by mood, color, type, and cultural tone.',
                    'The Archive and Glossary are designed as evergreen knowledge sections, while the Blog supports deeper editorial writing, SEO-focused explainers, design history, and practical guides for creative professionals.',
                ],
            ],
            'editorial' => [
                'title' => 'Editorial approach',
                'icon' => 'fa-pen-nib',
                'body' => [
                    'The platform favors useful, credited, and context-rich references over random image dumping. We look for work that teaches something about composition, hierarchy, typography, identity, editorial systems, web culture, poster design, color, or visual storytelling.',
                    'Legacy blog content is being cleaned and reorganized so that repeated titles, AI-output artifacts, placeholder fragments, and weak excerpts do not appear in the public experience.',
                ],
            ],
            'community' => [
                'title' => 'Community and submissions',
                'icon' => 'fa-users',
                'body' => [
                    'Users can create boards, save references, follow curators, and submit visual sources for review. Submissions are intended to become a moderated archive rather than an unfiltered upload stream.',
                    'Submitted references may be reviewed for source quality, copyright risk, visual relevance, tagging accuracy, and fit with the broader design culture focus of the site.',
                ],
            ],
            'funding' => [
                'title' => 'Funding and partnerships',
                'icon' => 'fa-handshake',
                'body' => [
                    'Visual Design Journey may earn revenue through tasteful sponsorships, partner placements, premium features, affiliate-style resource placements, and advertising where appropriate.',
                    'Commercial relationships do not determine editorial conclusions. Sponsored placements should be clearly labelled and designed to fit the platform without interruptive popups, noisy ad formats, or misleading endorsements.',
                ],
            ],
            'roadmap' => [
                'title' => 'Roadmap',
                'icon' => 'fa-route',
                'body' => [
                    'The near-term roadmap focuses on deeper multilingual support, better article translation workflows, stronger board and curator pages, movement detail pages, cleaner SEO metadata, submission moderation, and reliable user actions such as follow, save, like, and create board.',
                    'Premium tools may include private collections, source discovery workflows, PDF or ZIP moodboard exports, presentation mode, saved search alerts, weekly trend reports, and curator analytics.',
                ],
            ],
            'contact' => [
                'title' => 'Contact',
                'icon' => 'fa-envelope',
                'body' => [
                    'For editorial questions, copyright requests, partnership ideas, privacy questions, or corrections, contact Visual Design Journey through the contact page or by email.',
                ],
            ],
        ],
        'principles_title' => 'Guiding principles',
        'principles' => [
            ['Curated over crowded', 'The site should feel like a useful archive, not a noisy image dump.'],
            ['Context matters', 'References are stronger when connected to movements, styles, designers, and use cases.'],
            ['Lightweight by default', 'The product should stay fast, readable, SEO-friendly, and easy to browse.'],
            ['Respect attribution', 'Credits, source links, copyright review, and correction paths matter.'],
        ],
        'cta_title' => 'Want to contribute or partner?',
        'cta_body' => 'Send a reference, suggest a correction, or reach out about sponsorships and design-tool partnerships.',
        'contact_cta' => 'Contact us',
        'submit_cta' => 'Submit reference',
    ],
    'tr' => [
        'page_title' => 'Hakkında — Visual Design Journey',
        'meta' => 'Visual Design Journey; moodboardlar, tasarım akımları, tipografi, renk, marka ve küratörlü yaratıcı referanslar için bağımsız bir görsel tasarım arşivi ve keşif platformudur.',
        'eyebrow' => 'Platform hakkında',
        'title' => 'Visual Design Journey',
        'intro' => 'Visual Design Journey; grafik tasarımcılar, öğrenciler, küratörler, sanat yönetmenleri, marka kurucular ve meraklı görsel düşünürler için bağımsız bir tasarım ilhamı ve görsel kültür platformudur.',
        'updated' => 'Son güncelleme',
        'toc' => 'Bu sayfada',
        'toc_items' => [
            'mission' => ['Misyon', 'fa-compass'],
            'platform' => ['Platform neleri kapsar', 'fa-layer-group'],
            'editorial' => ['Editoryal yaklaşım', 'fa-pen-nib'],
            'community' => ['Topluluk ve gönderiler', 'fa-users'],
            'funding' => ['Finansman ve partnerlikler', 'fa-handshake'],
            'roadmap' => ['Yol haritası', 'fa-route'],
            'contact' => ['İletişim', 'fa-envelope'],
        ],
        'stats' => [
            ['Boardlar', 'Stil, referans ve araştırma için küratörlü görsel koleksiyonlar.'],
            ['Arşiv', 'Tasarım akımları, görsel özellikler, zaman çizgileri ve ilgili referanslar.'],
            ['Vibe', 'Mood, tipografi, renk ve stil üzerinden estetik keşif.'],
        ],
        'sections' => [
            'mission' => [
                'title' => 'Misyon',
                'icon' => 'fa-compass',
                'body' => [
                    'Visual Design Journey’nin amacı görsel kültürü gezmeyi, karşılaştırmayı, öğrenmeyi ve kaynak göstermeyi kolaylaştırmaktır. İlhamı sonsuz bir akış gibi sunmak yerine referansları boardlar, akımlar, vibe’lar, yazılar, renk paletleri ve küratör profilleri içinde düzenler.',
                    'Site, görsel işlerin bağlamına ihtiyaç duyan kişiler için tasarlanmıştır: hangi stile ait olduğu, hangi tarihsel akımla ilişkili olduğu, nasıl bir tipografi veya palet önerdiği ve gerçek bir yaratıcı projeyi nasıl destekleyebileceği önemlidir.',
                ],
            ],
            'platform' => [
                'title' => 'Platform neleri kapsar',
                'icon' => 'fa-layer-group',
                'body' => [
                    'Visual Design Journey birbirine bağlı keşif alanlarından oluşur. Boardlar bir tema veya proje etrafında görsel referans toplar. Küratörler referansları public profillerde düzenler. Trends yükselen stilleri ve boardları gösterir. Vibe; mood, renk, tip ve kültürel ton üzerinden estetik keşfi destekler.',
                    'Archive ve Glossary kalıcı bilgi bölümleri olarak tasarlanmıştır. Blog ise daha derin editoryal yazılar, SEO odaklı açıklayıcı içerikler, tasarım tarihi ve yaratıcı profesyoneller için pratik rehberler sunar.',
                ],
            ],
            'editorial' => [
                'title' => 'Editoryal yaklaşım',
                'icon' => 'fa-pen-nib',
                'body' => [
                    'Platform rastgele görsel yığmak yerine faydalı, kaynaklı ve bağlamı güçlü referansları öne çıkarır. Kompozisyon, hiyerarşi, tipografi, kimlik, editoryal sistemler, web kültürü, afiş tasarımı, renk veya görsel hikaye anlatımı hakkında bir şey öğreten işleri ararız.',
                    'Eski blog içerikleri temizlenip yeniden düzenlenmektedir; tekrar başlıklar, AI-output kalıntıları, placeholder parçaları ve zayıf excerpt’ler public deneyimde görünmemelidir.',
                ],
            ],
            'community' => [
                'title' => 'Topluluk ve gönderiler',
                'icon' => 'fa-users',
                'body' => [
                    'Kullanıcılar board oluşturabilir, referans kaydedebilir, küratörleri takip edebilir ve incelenmek üzere görsel kaynak gönderebilir. Gönderiler filtresiz bir yükleme akışı değil, moderasyonlu bir arşiv akışı olarak düşünülür.',
                    'Gönderilen referanslar kaynak kalitesi, telif riski, görsel uygunluk, etiket doğruluğu ve sitenin tasarım kültürü odağına uyum açısından incelenebilir.',
                ],
            ],
            'funding' => [
                'title' => 'Finansman ve partnerlikler',
                'icon' => 'fa-handshake',
                'body' => [
                    'Visual Design Journey; seçilmiş sponsorluklar, partner yerleşimleri, premium özellikler, kaynak yerleşimleri ve uygun olduğunda reklam yoluyla gelir elde edebilir.',
                    'Ticari ilişkiler editoryal sonucu belirlemez. Sponsorlu yerleşimler açıkça etiketlenmeli ve platforma kesintiye uğratıcı pop-up’lar, gürültülü reklam formatları veya yanıltıcı öneriler olmadan uyum sağlamalıdır.',
                ],
            ],
            'roadmap' => [
                'title' => 'Yol haritası',
                'icon' => 'fa-route',
                'body' => [
                    'Yakın dönem yol haritası; daha derin çok dilli destek, daha iyi makale çeviri akışları, güçlü board ve küratör sayfaları, akım detay sayfaları, temiz SEO metadata, gönderi moderasyonu ve follow, save, like, create board gibi güvenilir kullanıcı aksiyonlarına odaklanır.',
                    'Premium araçlar arasında özel koleksiyonlar, kaynak keşif akışları, PDF veya ZIP moodboard export, sunum modu, kayıtlı aramalar, haftalık trend raporları ve küratör analitiği olabilir.',
                ],
            ],
            'contact' => [
                'title' => 'İletişim',
                'icon' => 'fa-envelope',
                'body' => [
                    'Editoryal sorular, telif talepleri, partnerlik fikirleri, gizlilik soruları veya düzeltmeler için Visual Design Journey ile iletişim sayfası ya da e-posta üzerinden iletişime geçebilirsiniz.',
                ],
            ],
        ],
        'principles_title' => 'Temel ilkeler',
        'principles' => [
            ['Kalabalık değil kürasyon', 'Site gürültülü bir görsel yığını değil, işe yarar bir arşiv gibi hissettirmelidir.'],
            ['Bağlam önemlidir', 'Referanslar akımlar, stiller, tasarımcılar ve kullanım senaryolarıyla bağlanınca güçlenir.'],
            ['Varsayılan olarak hafif', 'Ürün hızlı, okunabilir, SEO dostu ve gezmesi kolay kalmalıdır.'],
            ['Atıfa saygı', 'Kredi, kaynak linki, telif incelemesi ve düzeltme yolları önemlidir.'],
        ],
        'cta_title' => 'Katkı sunmak veya partner olmak ister misin?',
        'cta_body' => 'Referans gönder, düzeltme öner ya da sponsorluk ve tasarım aracı partnerlikleri için iletişime geç.',
        'contact_cta' => 'İletişime geç',
        'submit_cta' => 'Referans gönder',
    ],
    'de' => [
        'page_title' => 'Über uns — Visual Design Journey',
        'meta' => 'Visual Design Journey ist ein unabhängiges Archiv und eine Discovery-Plattform für visuelles Design, Moodboards, Designbewegungen, Typografie, Farbe, Branding und kuratierte kreative Referenzen.',
        'eyebrow' => 'Über die Plattform',
        'title' => 'Visual Design Journey',
        'intro' => 'Visual Design Journey ist eine unabhängige Plattform für Designinspiration und visuelle Kultur, gemacht für Grafikdesigner, Studierende, Kuratoren, Art Directors, Markenentwickler und neugierige visuelle Denker.',
        'updated' => 'Zuletzt aktualisiert',
        'toc' => 'Auf dieser Seite',
        'toc_items' => [
            'mission' => ['Mission', 'fa-compass'],
            'platform' => ['Was die Plattform umfasst', 'fa-layer-group'],
            'editorial' => ['Redaktioneller Ansatz', 'fa-pen-nib'],
            'community' => ['Community und Einreichungen', 'fa-users'],
            'funding' => ['Finanzierung und Partnerschaften', 'fa-handshake'],
            'roadmap' => ['Roadmap', 'fa-route'],
            'contact' => ['Kontakt', 'fa-envelope'],
        ],
        'stats' => [
            ['Boards', 'Kuratierte visuelle Sammlungen für Stil, Referenz und Recherche.'],
            ['Archiv', 'Designbewegungen, visuelle Merkmale, Timelines und verwandte Referenzen.'],
            ['Vibe', 'Ästhetische Discovery über Stimmung, Typografie, Farbe und Stil.'],
        ],
        'sections' => [
            'mission' => [
                'title' => 'Mission',
                'icon' => 'fa-compass',
                'body' => [
                    'Das Ziel von Visual Design Journey ist es, visuelle Kultur leichter durchsuchbar, vergleichbar, lernbar und zitierbar zu machen. Inspiration wird nicht als endloser Feed behandelt, sondern in Boards, Bewegungen, Vibes, Artikel, Farbpaletten und Kuratorprofile organisiert.',
                    'Die Website richtet sich an Menschen, die Kontext zu visueller Arbeit brauchen: welchem Stil sie angehört, mit welcher historischen Bewegung sie verbunden ist, welche Typografie oder Palette sie nahelegt und wie sie ein reales Kreativprojekt unterstützen kann.',
                ],
            ],
            'platform' => [
                'title' => 'Was die Plattform umfasst',
                'icon' => 'fa-layer-group',
                'body' => [
                    'Visual Design Journey verbindet mehrere Discovery-Bereiche. Boards sammeln visuelle Referenzen rund um ein Thema oder Projekt. Kuratoren organisieren Referenzen in öffentlichen Profilen. Trends zeigen aufkommende Stile und Boards. Vibe unterstützt ästhetische Discovery über Stimmung, Farbe, Typografie und kulturellen Ton.',
                    'Archiv und Glossar sind als langfristige Wissensbereiche gedacht. Der Blog unterstützt tiefere redaktionelle Texte, SEO-fokussierte Erklärstücke, Designgeschichte und praktische Guides für Kreativprofis.',
                ],
            ],
            'editorial' => [
                'title' => 'Redaktioneller Ansatz',
                'icon' => 'fa-pen-nib',
                'body' => [
                    'Die Plattform bevorzugt nützliche, gut zugeschriebene und kontextreiche Referenzen statt zufälliger Bildablagen. Wir suchen Arbeiten, die etwas über Komposition, Hierarchie, Typografie, Identität, Editorialsysteme, Webkultur, Posterdesign, Farbe oder visuelles Storytelling vermitteln.',
                    'Legacy-Bloginhalte werden bereinigt und neu organisiert, damit doppelte Titel, AI-Output-Artefakte, Placeholder-Fragmente und schwache Auszüge nicht in der öffentlichen Experience erscheinen.',
                ],
            ],
            'community' => [
                'title' => 'Community und Einreichungen',
                'icon' => 'fa-users',
                'body' => [
                    'Nutzer können Boards erstellen, Referenzen speichern, Kuratoren folgen und visuelle Quellen zur Prüfung einreichen. Einreichungen sollen ein moderiertes Archiv bilden, keinen ungefilterten Upload-Stream.',
                    'Eingereichte Referenzen können nach Quellenqualität, Copyright-Risiko, visueller Relevanz, Tagging-Genauigkeit und Passung zum Designkultur-Fokus der Website geprüft werden.',
                ],
            ],
            'funding' => [
                'title' => 'Finanzierung und Partnerschaften',
                'icon' => 'fa-handshake',
                'body' => [
                    'Visual Design Journey kann Einnahmen durch geschmackvolle Sponsorings, Partnerplatzierungen, Premium-Funktionen, Ressourcenplatzierungen und, wo sinnvoll, Werbung erzielen.',
                    'Kommerzielle Beziehungen bestimmen keine redaktionellen Schlüsse. Gesponserte Platzierungen sollten klar gekennzeichnet sein und ohne störende Popups, laute Anzeigenformate oder irreführende Empfehlungen zur Plattform passen.',
                ],
            ],
            'roadmap' => [
                'title' => 'Roadmap',
                'icon' => 'fa-route',
                'body' => [
                    'Die kurzfristige Roadmap konzentriert sich auf tiefere Mehrsprachigkeit, bessere Artikel-Übersetzungsabläufe, stärkere Board- und Kuratorseiten, Detailseiten zu Bewegungen, saubere SEO-Metadaten, Moderation von Einreichungen und verlässliche Nutzeraktionen wie Folgen, Speichern, Liken und Board-Erstellung.',
                    'Premium-Tools können private Sammlungen, Quellensuche, PDF- oder ZIP-Moodboard-Exporte, Präsentationsmodus, gespeicherte Suchen, wöchentliche Trendberichte und Kurator-Analytics umfassen.',
                ],
            ],
            'contact' => [
                'title' => 'Kontakt',
                'icon' => 'fa-envelope',
                'body' => [
                    'Für redaktionelle Fragen, Copyright-Anfragen, Partnerschaftsideen, Datenschutzfragen oder Korrekturen kannst du Visual Design Journey über die Kontaktseite oder per E-Mail erreichen.',
                ],
            ],
        ],
        'principles_title' => 'Leitprinzipien',
        'principles' => [
            ['Kuration statt Überfüllung', 'Die Website soll wie ein nützliches Archiv wirken, nicht wie eine laute Bildablage.'],
            ['Kontext zählt', 'Referenzen werden stärker, wenn sie mit Bewegungen, Stilen, Designern und Anwendungsfällen verbunden sind.'],
            ['Standardmäßig leichtgewichtig', 'Das Produkt soll schnell, lesbar, SEO-freundlich und einfach zu durchsuchen bleiben.'],
            ['Attribution respektieren', 'Credits, Quellenlinks, Copyright-Prüfung und Korrekturwege sind wichtig.'],
        ],
        'cta_title' => 'Möchtest du beitragen oder Partner werden?',
        'cta_body' => 'Reiche eine Referenz ein, schlage eine Korrektur vor oder kontaktiere uns zu Sponsoring und Design-Tool-Partnerschaften.',
        'contact_cta' => 'Kontakt aufnehmen',
        'submit_cta' => 'Referenz einreichen',
    ],
];

$copy = $aboutCopy[$lang] ?? $aboutCopy['en'];

$pageTitle = $copy['page_title'];
$metaDesc = $copy['meta'];
$activeNav = '';
$canonicalUrl = publicUrl(vdj_localized_url('/about'));
$headExtra = '<script type="application/ld+json">' . json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'AboutPage',
    'name' => $copy['page_title'],
    'description' => $copy['meta'],
    'url' => $canonicalUrl,
    'isPartOf' => [
        '@type' => 'WebSite',
        'name' => 'Visual Design Journey',
        'url' => publicUrl('/'),
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';

require_once __DIR__ . '/includes/header.php';
?>

<style>
.about-hero {
  border-bottom: 1px solid var(--border);
  padding: 64px 0 48px;
}
.about-hero__inner {
  max-width: 980px;
}
.about-hero__badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 6px 12px;
  border-radius: var(--r-pill);
  background: var(--accent-soft);
  color: var(--accent);
  font-size: 12px;
  font-weight: 800;
  letter-spacing: .08em;
  text-transform: uppercase;
  margin-bottom: 18px;
}
.about-hero__title {
  margin: 0 0 18px;
  max-width: 760px;
}
.about-hero__sub {
  max-width: 760px;
  line-height: 1.75;
  margin: 0 0 22px;
}
.about-hero__date {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: var(--muted);
  font-size: 13px;
}
.about-stat-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 14px;
  margin-top: 34px;
}
.about-stat {
  border: 1px solid var(--border);
  border-radius: 10px;
  padding: 18px;
  background: var(--surface);
}
.about-stat strong {
  display: block;
  font-size: 18px;
  margin-bottom: 6px;
}
.about-stat span {
  color: var(--muted);
  font-size: 13px;
  line-height: 1.55;
}
.about-body {
  padding: 48px 0 84px;
}
.about-body__inner {
  display: grid;
  grid-template-columns: 240px 1fr;
  gap: 56px;
  align-items: start;
}
.about-toc {
  position: sticky;
  top: calc(var(--nav-h) + 24px);
}
.about-toc__label {
  font-size: 10px;
  font-weight: 800;
  letter-spacing: .14em;
  text-transform: uppercase;
  color: var(--muted);
  margin: 0 0 12px;
}
.about-toc__list {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.about-toc__link {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 9px 12px;
  border-radius: var(--r-md);
  color: var(--muted);
  font-size: 13px;
  font-weight: 600;
  line-height: 1.35;
  text-decoration: none;
}
.about-toc__link:hover {
  background: var(--accent-soft);
  color: var(--accent);
}
.about-toc__link i {
  width: 14px;
  text-align: center;
  color: var(--accent);
}
.about-mobile-toc {
  display: none;
  gap: 8px;
  overflow-x: auto;
  margin-bottom: 32px;
  padding-bottom: 4px;
  scrollbar-width: none;
}
.about-mobile-toc::-webkit-scrollbar { display: none; }
.about-mobile-toc a {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 7px 14px;
  border: 1px solid var(--border);
  border-radius: var(--r-pill);
  color: var(--muted);
  white-space: nowrap;
  text-decoration: none;
  font-size: 12px;
  font-weight: 700;
}
.about-content {
  min-width: 0;
}
.about-section {
  margin-bottom: 54px;
  scroll-margin-top: calc(var(--nav-h) + 32px);
}
.about-section__heading {
  display: flex;
  align-items: center;
  gap: 12px;
  padding-bottom: 14px;
  border-bottom: 1px solid var(--border);
  margin: 0 0 20px;
  font-size: 22px;
  letter-spacing: -.02em;
}
.about-section__heading i {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 8px;
  background: var(--accent-soft);
  color: var(--accent);
  font-size: 14px;
}
.about-section p {
  color: var(--muted);
  line-height: 1.78;
  margin: 0 0 16px;
}
.about-principles {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
}
.about-principle {
  border: 1px solid var(--border);
  border-radius: 10px;
  padding: 18px;
  background: var(--surface);
}
.about-principle strong {
  display: block;
  margin-bottom: 7px;
}
.about-principle span {
  color: var(--muted);
  font-size: 13px;
  line-height: 1.6;
}
.about-cta {
  border: 1px solid var(--border);
  border-radius: 12px;
  background: var(--surface);
  padding: 24px;
  display: flex;
  gap: 18px;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
}
.about-cta h2 {
  margin: 0 0 8px;
  font-size: 22px;
}
.about-cta p {
  margin: 0;
  color: var(--muted);
  line-height: 1.65;
}
.about-cta__actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}
@media (max-width: 900px) {
  .about-body__inner { grid-template-columns: 1fr; gap: 0; }
  .about-toc { display: none; }
  .about-mobile-toc { display: flex; }
}
@media (max-width: 700px) {
  .about-hero { padding: 44px 0 34px; }
  .about-stat-grid,
  .about-principles { grid-template-columns: 1fr; }
  .about-section { margin-bottom: 42px; }
  .about-cta { align-items: flex-start; }
}
</style>

<main class="page-wrap" id="main-content">
  <section class="about-hero">
    <div class="container about-hero__inner">
      <div class="about-hero__badge">
        <i class="fa-solid fa-circle-info"></i>
        <?= e($copy['eyebrow']) ?>
      </div>
      <h1 class="t-display about-hero__title"><?= e($copy['title']) ?></h1>
      <p class="t-body-lg text-muted about-hero__sub"><?= e($copy['intro']) ?></p>
      <p class="about-hero__date">
        <i class="fa-regular fa-calendar"></i>
        <?= e($copy['updated']) ?>: <strong><?= e(vdjLocalizedDate('2026-06-06')) ?></strong>
      </p>

      <div class="about-stat-grid" aria-label="Platform pillars">
        <?php foreach ($copy['stats'] as $stat): ?>
          <div class="about-stat">
            <strong><?= e($stat[0]) ?></strong>
            <span><?= e($stat[1]) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="about-body">
    <div class="container about-body__inner">
      <aside class="about-toc" aria-label="<?= e($copy['toc']) ?>">
        <p class="about-toc__label"><?= e($copy['toc']) ?></p>
        <nav>
          <ul class="about-toc__list">
            <?php foreach ($copy['toc_items'] as $id => $item): ?>
              <li>
                <a class="about-toc__link" href="#<?= e($id) ?>">
                  <i class="fa-solid <?= e($item[1]) ?>"></i><?= e($item[0]) ?>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        </nav>
      </aside>

      <div class="about-content">
        <nav class="about-mobile-toc" aria-label="<?= e($copy['toc']) ?>">
          <?php foreach ($copy['toc_items'] as $id => $item): ?>
            <a href="#<?= e($id) ?>"><i class="fa-solid <?= e($item[1]) ?>"></i><?= e($item[0]) ?></a>
          <?php endforeach; ?>
        </nav>

        <?php foreach ($copy['sections'] as $id => $section): ?>
          <article class="about-section" id="<?= e($id) ?>">
            <h2 class="about-section__heading">
              <i class="fa-solid <?= e($section['icon']) ?>"></i>
              <?= e($section['title']) ?>
            </h2>
            <?php foreach ($section['body'] as $paragraph): ?>
              <p><?= e($paragraph) ?></p>
            <?php endforeach; ?>
            <?php if ($id === 'contact'): ?>
              <p>
                <a href="<?= e(vdj_localized_url('/contact')) ?>"><?= e($copy['contact_cta']) ?></a>
                · <a href="mailto:a.ozelbicer@gmail.com">a.ozelbicer@gmail.com</a>
              </p>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>

        <section class="about-section" id="principles">
          <h2 class="about-section__heading">
            <i class="fa-solid fa-scale-balanced"></i>
            <?= e($copy['principles_title']) ?>
          </h2>
          <div class="about-principles">
            <?php foreach ($copy['principles'] as $principle): ?>
              <div class="about-principle">
                <strong><?= e($principle[0]) ?></strong>
                <span><?= e($principle[1]) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        </section>

        <section class="about-cta" aria-label="<?= e($copy['cta_title']) ?>">
          <div>
            <h2><?= e($copy['cta_title']) ?></h2>
            <p><?= e($copy['cta_body']) ?></p>
          </div>
          <div class="about-cta__actions">
            <a href="<?= e(vdj_localized_url('/contact')) ?>" class="btn btn--primary"><?= e($copy['contact_cta']) ?></a>
            <a href="<?= e(vdj_localized_url('/submit-source')) ?>" class="btn btn--secondary"><?= e($copy['submit_cta']) ?></a>
          </div>
        </section>
      </div>
    </div>
  </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
