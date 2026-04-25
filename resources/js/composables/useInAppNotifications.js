import { ref, shallowRef } from 'vue';
import axios from 'axios';

const notifications = shallowRef([]);
const unreadCount = ref(0);
const loading = ref(false);
let inFlight = null;

export function useInAppNotifications() {
  function load() {
    if (inFlight) {
      return inFlight;
    }

    loading.value = true;
    inFlight = axios
      .get(route('notifications.index'))
      .then(({ data }) => {
        notifications.value = data?.notifications ?? [];
        unreadCount.value = data?.unread_count ?? 0;
      })
      .catch(() => {
        notifications.value = [];
        unreadCount.value = 0;
      })
      .finally(() => {
        loading.value = false;
        inFlight = null;
      });

    return inFlight;
  }

  return {
    notifications,
    unreadCount,
    loading,
    load,
  };
}
