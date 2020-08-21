<template>
  <div class="modelarium-selectmultiple">
    <select :name="name" multiple="multiple" style="display: none;">
      <option
        v-for="item in selectionVisible"
        :key="item.id"
        :value="item.id"
      ></option>
    </select>
    <div class="modelarium-selectmultiple__header">
      <router-link
        :to="'/' + type + '/edit/'"
        target="_blank"
        title="Add a new value for this field"
        class="modelarium-selectmultiple__button"
      >
        <span>＋ ➕Add new</span>
      </router-link>
      <a
        href="#"
        class="modelarium-selectmultiple__button"
        title="Reload values of this field"
        @click.prevent="loadData"
      >
        <span>↻ Refresh</span>
      </a>
    </div>
    <div class="modelarium-selectmultiple__container">
      <div class="modelarium-selectmultiple__selectable">
        <input
          v-model="selectableQuery"
          type="text"
          class="modelarium-selectmultiple__search"
          autocomplete="off"
          placeholder="filter..."
        />
        <ul class="modelarium-selectmultiple__list" tabindex="-1" title="">
          <li
            v-for="item in selectionVisible"
            :key="item.id"
            class="modelarium-selectmultiple__item--selectable"
            @click="addItem(item)"
          >
            <slot v-bind:item="item">
              <span>{{ item[fieldName] }}</span>
            </slot>
          </li>
        </ul>
        <button
          type="button"
          class="modelarium-selectmultiple__all"
          @click="selectAll"
        >
          Select all ⇒
        </button>
      </div>
      <div class="modelarium-selectmultiple__decoration">⇄</div>
      <div class="modelarium-selectmultiple__selection">
        <input
          v-model="selectionQuery"
          type="text"
          class="modelarium-selectmultiple__search"
          autocomplete="off"
          placeholder="filter..."
        />
        <ul class="modelarium-selectmultiple__list" tabindex="-1" title="">
          <li
            v-for="item in selectionVisible"
            :key="item.id"
            class="modelarium-selectmultiple__item--selection"
            @click="removeItem(item)"
          >
            <slot v-bind:item="item">
              <span>{{ item[fieldName] }}</span>
            </slot>
          </li>
        </ul>
        <button
          type="button"
          class="modelarium-selectmultiple__all"
          @click="removeAll"
        >
          ⇐ Remove all
        </button>
      </div>
    </div>
    <p class="modelarium-selectmultiple__message modelarium__message">
      {{ errorMessage }}
    </p>
  </div>
</template>

<script>
export default {
  data() {
    return {
      errorMessage: "",
      selectable: [],
      selectableQuery: "",
      selectionQuery: "",
    };
  },

  props: {
    value: {
      type: Array,
      required: true,
    },

    /**
     * The form field name
     */
    name: {
      type: String,
      required: true,
    },

    /**
     * The form field type (as in the URL /type/:id)
     */
    type: {
      type: String,
      required: true,
    },

    /**
     * The field in the relationship that is used as a title
     */
    titleField: {
      type: String,
      required: true,
    },

    query: {
      type: Function,
      required: true,
    },
  },

  computed: {
    selectionVisible() {
      if (!this.selectionQuery) {
        return this.value;
      }
      return this.value.filter(
        (i) => i["this.titleField"].indexOf(this.selectionQuery) != -1
      );
    },
  },

  watch: {
    selectableQuery(newval) {
      this.loadData();
    },
  },

  methods: {
    loadData() {
      // TODO: load
      this.query(selectableQuery).then((data) => {
        this.$set(this, "selectable", data);
      });
    },

    addItem(item) {
      this.value.push(item);
    },

    removeItem(item) {
      this.value = this.value.filter((value) => item.id != value.id);
    },
  },
};
</script>

<style></style>
