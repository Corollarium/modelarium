<template>
  <div class="modelarium-multiple">
    <select :name="name" multiple="multiple" style="display: none">
      <option
        v-for="item in selectionVisible"
        :key="item.id"
        :value="item.id"
      ></option>
    </select>
    <div class="modelarium-multiple__header">
      <router-link
        :to="'/' + type + '/edit/'"
        target="_blank"
        title="Add a new value for this field"
        class="modelarium-multiple__button"
      >
        <span>＋ ➕Add new</span>
      </router-link>
      <a
        href="#"
        class="modelarium-multiple__button"
        title="Reload values of this field"
        @click.prevent="loadData"
      >
        <span>↻ Refresh</span>
      </a>
    </div>
    <div class="modelarium-multiple__container">
      <div class="modelarium-multiple__selectable">
        <input
          v-model="selectableQuery"
          type="text"
          class="modelarium-multiple__search"
          autocomplete="off"
          placeholder="filter..."
        />
        <ul class="modelarium-multiple__list" tabindex="-1" title="">
          <li
            v-for="item in selectionVisible"
            :key="item.id"
            class="modelarium-multiple__item--selectable"
            @click="addItem(item)"
          >
            <slot v-bind:item="item">
              <span>{{ item[fieldName] }}</span>
            </slot>
          </li>
        </ul>
        <button
          type="button"
          class="modelarium-multiple__all"
          @click="selectAll"
        >
          Select all ⇒
        </button>
      </div>
      <div class="modelarium-multiple__decoration">⇄</div>
      <div class="modelarium-multiple__selection">
        <input
          v-model="selectionQuery"
          type="text"
          class="modelarium-multiple__search"
          autocomplete="off"
          placeholder="filter..."
        />
        <ul class="modelarium-multiple__list" tabindex="-1" title="">
          <li
            v-for="item in selectionVisible"
            :key="item.id"
            class="modelarium-multiple__item--selection"
            @click="removeItem(item)"
          >
            <slot v-bind:item="item">
              <span>{{ item[fieldName] }}</span>
            </slot>
          </li>
        </ul>
        <button
          type="button"
          class="modelarium-multiple__all"
          @click="removeAll"
        >
          ⇐ Remove all
        </button>
      </div>
    </div>
    <p class="modelarium-multiple__message modelarium__message">
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
    /**
     * The form field name
     */
    name: {
      type: String,
    },
    /**
     * html classes applied on <select></select>
     */
    htmlClass: {
      type: String,
    },
    /**
     * The field in the relationship that is used as a title
     */
    titleField: {
      type: String,
    },
    /**
     * The target type, such as 'post'
     */
    targetType: {
      type: String,
    },
    /**
     * The target type plural, such as 'posts'
     */
    targetTypePlural: {
      type: String,
    },

    /**
     * The GraphQL query
     */
    query: {
      type: String,
    },

    /**
     * Is this field required?
     */
    required: {
      type: Boolean,
      default: false,
    },
  },

  computed: {
    selectionVisible() {
      if (!this.selectionQuery) {
        return this.value;
      }
      return this.value.filter(
        (i) => i[this.titleField].indexOf(this.selectionQuery) != -1
      );
    },
  },

  watch: {
    selectableQuery(newval) {
      this.loadData();
    },
  },

  methods: {
    async fetch() {
      this.isLoading = true;
      axios
        .post("/graphql", {
          query: this.query,
          variables: {
            page: 1,
            // TODO: query: selectableQuery,
            ...this.queryVariables,
          },
        })
        .then((result) => {
          if (result.data.errors) {
            // TODO
            console.error(result.data.errors);
            return;
          }
          const data = result.data.data;
          this.$set(this, "selectable", data[this.targetTypePlural].data);
        })
        .finally(() => {
          this.isLoading = false;
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
