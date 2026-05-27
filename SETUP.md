# WP Page Migrator — User Setup Guide

Welcome to **WP Page Migrator**! This guide will help you install, configure, and perform your first page migration using our high-reliability task engine.

---

## 🛠️ 1. Installation & Build

Since this plugin uses a modern React interface, you need to compile the assets before the first use.

1.  **Upload:** Move the `wp-page-migrator` folder into your `/wp-content/plugins/` directory.
2.  **Install Dependencies:** Open your terminal in the plugin folder and run:
    ```bash
    npm install
    ```
3.  **Build Assets:** Compile the CSS and JavaScript:
    ```bash
    npm run build
    ```
4.  **Activate:** Go to the WordPress **Plugins** menu and click **Activate** on "WP Page Migrator".

---

## 📤 2. How to Export Pages

1.  **Navigate:** Go to **Tools > Page Migrator** in your WordPress sidebar.
2.  **Select Pages:** You will see a list of all your pages. Use the **Search Bar** to find specific pages or the **Select All** checkbox for bulk migrations.
3.  **Start Export:** Click the primary **"Export Selected"** button.
4.  **Monitor Progress:** The wizard will move to Step 2 ("Migrate"). You will see a progress bar. **Do not close the tab** until it reaches 100%.
5.  **Download:** Once complete, a success notice will appear with a **"Download Export Package"** button. Save this `.zip` file to your computer.

---

## 📥 3. How to Import Pages

1.  **Navigate:** Go to **Tools > Page Migrator** on the **destination site** and click the **"Import"** tab.
2.  **Upload:** Drag and drop your `.zip` export package into the upload zone, or click to select the file.
3.  **Configure (The "Pro" Step):**
    *   **Conflict Resolution:** Choose whether to skip existing pages, overwrite them, or create new copies.
    *   **Batch Sizes:** If you are on a slow server, lower the "Media Batch Size" to `1`. For fast servers, you can increase it to `20`.
4.  **Migrate:** Click **"Start Import Process"**.
5.  **Activity Log:** You can watch the "Activity Log" (terminal-style) to see exactly which pages and images are being imported in real-time.

---

## 💡 4. Pro Tips for a 10/10 Experience

*   **Flexible Import:** You can import pages even if the destination site doesn't have **ACF** or **Elementor** installed. The plugin will store the data safely, and the layouts will "wake up" once you install those plugins later.
*   **Permissions:** Ensure your `/wp-content/uploads/` directory is writable, as the plugin creates a temporary `wpm-exports` and `wpm-imports` folder to handle the packages.
*   **Large Media:** If your migration hangs on images, simply reload and try again with a **Media Batch Size of 1**. Our Task-Runner is designed to be resilient!

---

## 🆘 Troubleshooting

*   **UI Not Loading?** Ensure you ran `npm run build`.
*   **Import Error?** Check the **Activity Log** in the Import tab for specific error messages. Usually, this is due to a corrupted ZIP file or server memory limits.
*   **Missing Images?** The plugin identifies images using standard `wp-image-{ID}` classes. Custom-coded image paths may need manual updating.

---
*Developed with ❤️ by the Gemini CLI Team.*
