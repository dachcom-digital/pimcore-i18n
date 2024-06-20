pimcore.registerNS('pimcore.plugin.i18n.objectPreview');
pimcore.plugin.i18n.objectPreview = Class.create({

    objectInstance: null,

    initialize: function () {
        document.addEventListener(pimcore.events.postOpenObject, (e) => {
            this.postOpenObject(e.detail.object, e.detail.type);
        });
    },

    postOpenObject: function(objectInstance) {
        if (!objectInstance.data.hasPreview) {
            return;
        }

        this.objectInstance = objectInstance;

        let sitesStore = pimcore.globalmanager.get('sites');
        if (sitesStore.isLoading()) {
            sitesStore.addListener('load', () => this.modifyObjectPreviewBtn());
            return;
        }

        this.modifyObjectPreviewBtn();
    },

    modifyObjectPreviewBtn: function() {
        let locales = pimcore.settings.websiteLanguages;
        let sitesStore = pimcore.globalmanager.get('sites');

        let index = this.objectInstance.toolbar.items.length;
        let origPreviewButton = this.objectInstance.toolbar.items.find(e => typeof e === 'object' && e.iconCls === 'pimcore_material_icon_preview pimcore_material_icon')

        if (origPreviewButton) {
            index = this.objectInstance.toolbar.items.indexOf(origPreviewButton);
            this.objectInstance.toolbar.remove(origPreviewButton);
        }

        let previewButton = this.objectInstance.toolbar.insert(index, {
            tooltip: t('open'),
            scale: 'medium',
            iconCls: 'pimcore_material_icon_preview pimcore_material_icon',
            menu: []
        });

        sitesStore.each((siteItem) => {
           if (locales.length === 1) {
               let locale = locales[0];
               previewButton.menu.insert({
                   text: siteItem.data.domain + ' [' + locale + ']',
                   handler: () => this.openObjectPreview({
                       i18n_locale: locale,
                       i18n_site: siteItem.data.id
                   })
               })
           } else {
               previewButton.menu.insert({
                   text: siteItem.data.domain,
                   menu: locales.map(locale => new Object({
                       text: locale,
                       handler: () => this.openObjectPreview({
                           i18n_locale: locale,
                           i18n_site: siteItem.data.id
                       })
                   }))
               });
           }
        });
    },

    openObjectPreview: function(params) {
        let url = Routing.generate('pimcore_admin_dataobject_dataobject_preview', {
            id: this.objectInstance.data.general.id,
            time: (new Date()).getTime(),
            ...params
        });
        this.objectInstance.saveToSession(() => {
            window.open(url);
        })
    }

});
new pimcore.plugin.i18n.objectPreview();
