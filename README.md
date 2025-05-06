# 🎬 YouTube Embed Plugin

Un plugin WordPress simple pour intégrer les dernières vidéos d'une chaîne YouTube dans vos articles et pages à l’aide d’un shortcode.  
Interface propre en Tailwind CSS, gestion des chaînes, aperçu vidéo et lien avec les catégories WordPress.

---

## 🚀 Fonctionnalités

- Ajouter plusieurs chaînes YouTube
- Associer chaque chaîne à une catégorie WordPress
- Afficher les miniatures des vidéos avec titre + lien
- Shortcode personnalisable `[yt_embed]`
- Interface admin en Tailwind CSS
- Chargement dynamique via l’API YouTube Data v3

---

## 🔧 Installation

1. Clonez le repo ou déposez le ZIP dans `wp-content/plugins/`
2. Activez le plugin depuis l'admin WordPress
3. Allez dans **YouTube Embed** dans le menu admin
4. Ajoutez votre clé API YouTube
5. Ajoutez une ou plusieurs chaînes

---

## ✏️ Utilisation du shortcode

```bash
[yt_embed channel="ID_DE_LA_CHAINE" layout="grid" count="6"]
[yt_embed channel="UC_x5XG1OV2P6uZZ5FSM9Ttw"]
[yt_embed channel="UC_x5XG1OV2P6uZZ5FSM9Ttw" layout="grid" count="6"]
[yt_embed channel="UC_x5XG1OV2P6uZZ5FSM9Ttw" layout="list" count="6"]
```



🔐 Clé API YouTube
Créez une clé via : https://console.developers.google.com/
Activez l'API YouTube Data API v3
Collez la clé dans l’interface admin du plugin

🤝 Contribuer
Forkez le projet
Créez une branche : git checkout -b feature/ma-feature
Envoyez une PR ❤️

🧠 À venir
Synchronisation automatique avec wp_cron
Création automatique d’articles WordPress à partir des vidéos
Filtrage par catégorie dans les shortcodes


🐛 Bugs ? Suggestions ?
Créez une issue sur GitHub ou envoyez-moi un message 😄

📜 Licence
MIT – Faites-en bon usage !

