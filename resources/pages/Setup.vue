<template>
	<v-container fluid>
		<v-row>
			<v-col cols="6">
				<AuiCard title="App Settings">
					<v-switch v-model="form.xero_enabled" label="Automatically Sync Orders" />
					<AuiInputText v-model="form.xero_client_id" :label="id.label" :error="formErrors.xero_client_id" />
					<AuiInputPassword
						v-model="form.xero_client_secret"
						:label="secret.label"
						:error="formErrors.xero_client_secret"
					/>
					<AuiInputText
						v-model="form.xero_account_id"
						:label="accountId.label"
						:error="formErrors.xero_account_id"
					/>
					<v-alert type="info">
						Redirect URL needs to be added to app setting:<br />
						<strong>{{ props.adminui?.appUrl }}/admin/xero/connect</strong>
					</v-alert>
				</AuiCard>
			</v-col>
			<v-col cols="6">
				<AuiCard title="Connection">
					<div v-if="props.xeroTenant">
						<div>
							Connected as <span class="font-weight-bold">{{ props.xeroTenant }}</span>
						</div>
						<v-list>
							<v-simple-table>
								<tbody>
									<tr>
										<td>Scopes</td>
										<td class="caption">{{ props.xeroToken.scopes }}</td>
									</tr>
									<tr>
										<td>Connected on</td>
										<td>{{ connectedDate }}</td>
									</tr>
									<tr>
										<td>Last accessed</td>
										<td>{{ accessedDate }}</td>
									</tr>
									<tr>
										<td>Connection type</td>
										<td class="text-capitalize">{{ props.xeroToken.tenant_type }}</td>
									</tr>
								</tbody>
							</v-simple-table>
						</v-list>
						<div class="mt-8 text-right">
							<v-btn color="error" @click="disconnect">Disconnect</v-btn>
						</div>
					</div>
					<div v-else>
						<div>Not currently connected</div>
						<div class="mt-8">
							<v-btn color="primary" @click="connect">Connect Xero</v-btn>
						</div>
					</div>
				</AuiCard>
				<AuiCard title="Contacts" class="mt-4">
					<v-simple-table v-if="props.xeroContacts">
						<tbody>
							<tr>
								<td>Contacts</td>
								<td class="caption">{{ props.xeroContacts.length }}</td>
							</tr>
						</tbody>
					</v-simple-table>
					<div class="mt-8 d-flex justify-space-between">
						<div>
							<small>
								This will sync your current accounts on Xero to your accounts on AdminUI<br />
								If this is not done new accounts may not sync when assigning orders to them.<br />
							</small>
						</div>
						<div>
							<v-btn color="error" @click="sync">Sync Contacts</v-btn>
						</div>
					</div>
				</AuiCard>
			</v-col>
		</v-row>
	</v-container>
</template>

<script setup>
import { computed } from "vue";
const { useApiForm, useRoute, axios, router } = window.$adminui;

const props = defineProps({
	xeroContacts: {
		type: Array,
		default: () => []
	},
	xeroSettings: {
		type: Array,
		default: () => []
	},
	xeroTenant: {
		type: String,
		default: ""
	},
	xeroToken: {
		type: Object,
		default: () => ({})
	},
	adminui: {
		type: Object,
		default: () => ({})
	}
});

const route = useRoute();
const enabled = computed(() => props.xeroSettings.find((s) => s.name === "xero_enabled"));
const id = computed(() => props.xeroSettings.find((s) => s.name === "xero_client_id"));
const secret = computed(() => props.xeroSettings.find((s) => s.name === "xero_client_secret"));
const accountId = computed(() => props.xeroSettings.find((s) => s.name === "xero_account_id"));

const getInitialData = () => {
	return props.xeroSettings.reduce((acc, curr) => {
		const value = curr.value_cast === "integer" ? +curr.value : curr.value;
		acc[curr.name] = value;
		return acc;
	}, {});
};

let { form, formErrors } = useApiForm({ route: "admin.api.config.preferences", initialData: getInitialData() });

const connect = async () => {
	window.location.href = route("admin.setup.xero.connect");
};
const sync = async () => {
	router.get(route("admin.setup.xero.sync.contacts"));
};

const disconnect = async () => {
	await axios.delete(route("admin.setup.xero.disconnect"));
	router.reload({
		preserveScroll: true,
		only: ["xeroContacts", "xeroTenant"]
	});
};

const connectedDate = computed(() => (props.xeroTenant ? new Date(props.xeroToken.created_at).toLocaleString() : null));
const accessedDate = computed(() => (props.xeroTenant ? new Date(props.xeroToken.updated_at).toLocaleString() : null));
</script>
