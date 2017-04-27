Ext.require('Cetera.model.MailEvent');
Ext.create('Ext.data.Store',{
	storeId: 'mail_events',
	model: 'Cetera.model.MailEvent'		
});
		
Ext.define('Cetera.grid.MailTemplates', {

    extend:'Cetera.grid.Abstract',
	requires: 'Cetera.model.MailTemplate',
	
	border: false,	
	
	editWindowClass: 'Cetera.window.MailTemplate',

    columns: [
		{text: "ID", width: 50, dataIndex: 'id'},
		{text: "Акт.",  width: 60, dataIndex: 'active', renderer: function (value) { if (value) return 'Да'; else return 'Нет'; }},
		{
			text: "Событие",  
			flex: 1, 
			dataIndex: 'event', 
			renderer: function (value) { 
				var evt = this.mailEvents.getById(value);
				value = '['+value+']';
				if (evt) value += ' ' + evt.get('name');
				return value;
			}
		},
        {text: "Тема",  flex: 1, dataIndex: 'mail_subject'}
    ],
	
	store: {
		model: 'Cetera.model.MailTemplate',
		autoLoad: true,
		autoSync: true			
	},
	
	initComponent: function() {
		this.mailEvents = Ext.data.StoreManager.lookup('mail_events');
		this.mailEvents.load();
		this.callParent();		
	}
	
});