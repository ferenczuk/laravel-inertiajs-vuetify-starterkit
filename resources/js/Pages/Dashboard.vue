<script setup>
import CentralLayout from '@/Layouts/CentralLayout.vue'
import Breadcrumbs from '@/Components/Breadcrumbs.vue'
import { Head } from '@inertiajs/vue3'
</script>

<template>
  <Head title="Dashboard" />
  <CentralLayout>
    <div class="mb-5">
      <h5 class="text-h5 font-weight-bold">Dashboard</h5>
      <Breadcrumbs :items="breadcrumbs" class="pa-0 mt-1" />
    </div>
    <v-card>
      <v-card-text>
        <div class="text-h6 text-medium-emphasis">Welcome back, {{ $page.props.auth.user.name }}!</div>
      </v-card-text>
    </v-card>
  </CentralLayout>
</template>

<script>
export default {
  name: 'DashboardPage',
  data() {
    return {
      breadcrumbs: [
        {
          title: 'Dashboard oi',
          disabled: true,
        },
      ],
    }
  },
}
</script>
