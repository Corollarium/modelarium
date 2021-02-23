<template>
  <div class="modelarium-autocomplete" :data-attribute="name">
    <select :name="name" :multiple="isMultiple" style="display: none">
      <option
        v-for="item in selectionVisible"
        :key="item.id"
        :value="item.id"
        selected="selected"
      ></option>
    </select>
    <div class="modelarium-autocomplete__container">
      <vue-simple-suggest
        v-model="selectableQuery"
        :styles="{
          defaultInput: 'form-control autocomplete-input',
          suggestions: 'modelarium-autocomplete__result-list',
          suggestItem: 'modelarium-autocomplete__result-item',
        }"
        :display-attribute="titleField"
        :value-attribute="valueField"
        :debounce="debounceTime"
        :list="fetch"
        :placeholder="placeholder"
        @select="onSelect"
      >
        <template slot="misc-item-above" slot-scope="{ suggestions, query }">
          <div class="modelarium-autocomplete__total-results">
            <!-- <span>{{ messages["Busca:"] }} {{ query }}.</span> -->
            {{ messages["Total results:"] }}
            {{ paginatorInfo.total }}
          </div>
          <hr />
        </template>
      </vue-simple-suggest>

      <router-link
        v-if="canCreate"
        :to="'/' + targetType + '/edit/'"
        target="_blank"
        title="Add a new value for this field"
        class="modelarium-autocomplete__button"
      >
        <span>＋ {{ messages["Add new"] }}</span>
      </router-link>
      <div v-if="isMultiple" class="modelarium-autocomplete__selection">
        <ul class="modelarium-autocomplete__list" tabindex="-1" title="">
          <li
            v-for="item in selectionVisible"
            :key="item.id"
            class="modelarium-autocomplete__item--selection"
            @click="removeItem(item)"
          >
            <slot :item="item">
              <span>{{ item[titleField] }}</span>
            </slot>
          </li>
        </ul>
        <button
          type="button"
          class="modelarium-autocomplete__all"
          @click="removeAll"
        >
          {{ messages["Remove all"] }}
        </button>
      </div>
    </div>
    <p class="modelarium-autocomplete__message modelarium__message">
      {{ errorMessage }}
    </p>
  </div>
</template>

<script>
import ajax from "../../code/ajax";
import VueSimpleSuggest from "vue-simple-suggest";

export default {
  components: {
    VueSimpleSuggest,
  },
  props: {
    value: {
      type: [String, Number, Array, Object],
      default: undefined,
    },

    /**
     * The form field name
     */
    name: {
      type: String,
      required: true,
    },

    debounceTime: {
      type: Number,
      default: 200, // in milliseconds
    },

    /**
     * The field in the relationship model that is used as a title
     */
    titleField: {
      type: String,
      required: true,
    },

    /**
     * The field in the relationship model that is used to query
     */
    queryField: {
      type: String,
      required: true,
    },

    /**
     * The field in the relationship model that is used to query.
     * Set as undefined to return the entire entry object.
     */
    valueField: {
      type: String,
      default: "id",
    },

    /**
     * The target type, such as 'post'
     */
    targetType: {
      type: String,
      required: true,
    },

    /**
     * The target type plural, such as 'posts'
     */
    targetTypePlural: {
      type: String,
      required: true,
    },

    /**
     * The GraphQL query
     */
    query: {
      type: String,
      required: true,
    },

    /**
     * Is this field required?
     */
    required: {
      type: Boolean,
      default: false,
    },

    /**
     * If true accepts multiple values.
     */
    isMultiple: {
      type: Boolean,
      default: false,
    },

    /**
     * Placeholder message
     */
    placeholder: {
      type: String,
      default: undefined,
    },

    /**
     * Translatable messages
     */
    messages: {
      type: Object,
      default: () => {
        switch (Vue.$locale) {
          case "pt":
          case "pt_PT":
          case "pt_BR":
            return {
              "No results found": "Nada achado",
              "Total results:": "Total achado:",
              "Remove all": "Remover tudo",
              "Add new": "Criar",
              "search...": "buscar...",
            };
          case "en":
          default:
            return {
              "No results found": "No results found",
              "Total results:": "Total results:",
              "Remove all": "Remove all",
              "Add new": "Add new",
              "search...": "search...",
            };
        }
      },
    },

    canCreate: {
      // TODO
      type: Boolean,
      default: false,
    },

    // TODO
    prefetch: {
      type: Boolean,
      default: false,
    },
  },

  data() {
    return {
      /**
       * Actual values
       */
      actualValues: undefined,

      /**
       * If some error happened.
       */
      errorMessage: "",
      /**
       * Results from the server
       */
      selectable: [],
      /**
       * What the user is typing
       */
      selectableQuery: "",
      /**
       * Pagination about returned results
       */
      paginatorInfo: {
        currentPage: 0,
        lastPage: 0,
        perPage: 0,
        total: -1, // -1: nothing yet
      },
    };
  },

  computed: {
    selectionVisible() {
      if (!this.selectionQuery) {
        return this.actualValues;
      }
      return this.actualValues.filter(
        (i) => i[this.titleField].indexOf(this.selectionQuery) != -1
      );
    },
    placeholderComputed() {
      if (this.placeholder || this.placeholder === null) {
        return this.placeholder;
      }
      return this.messages["search..."];
    },
  },

  watch: {
    selectableQuery(newval) {
      if (this.isMultiple) {
        // TODO
      } else {
        if (newval) {
          return;
        }
        this.actualValues = undefined;
        this.$emit("input", this.actualValues);
      }
    },
  },

  mounted() {
    this.actualValues = this.isMultiple ? [] : undefined;
    if (this.value) {
      this.selectableQuery = this.value;
    }
    // TODO: check multiple case
  },

  methods: {
    saveSelectionAndReset(e) {
      let val = e.target.value;
      if (val) {
        this.optionVal = val;
      }
      e.target.value = "";
    },

    /**
     * push submit events.
     */
    onSelect(result) {
      const v = this.valueField ? result[this.valueField] : result;

      if (this.isMultiple) {
        this.actualValues.push(v);
      } else {
        if (result) {
          this.$set(this, "actualValues", v);
        } else {
          this.$set(this, "actualValues", undefined);
        }
      }
      this.$emit("input", this.actualValues);
    },

    async fetch() {
      this.isLoading = true;
      return ajax
        .post("/graphql", {
          query: this.query,
          variables: {
            page: 1,
            [this.queryField]: this.selectableQuery,
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
          const results = data[this.targetTypePlural].data;
          const paginatorInfo = data[this.targetTypePlural].paginatorInfo;
          this.$set(this, "selectable", results);
          this.$set(this, "paginatorInfo", paginatorInfo);
          return results;
        })
        .finally(() => {
          this.isLoading = false;
        });
    },

    addItem(item) {
      this.actualValues.push(item);
    },

    removeItem(item) {
      this.actualValues = this.actualValues.filter(
        (value) => item.id != value.id
      );
    },

    removeAll() {
      this.$set(this, "value", []);
    },
  },
};
</script>

<style>
.modelarium-autocomplete__result-list {
  margin: 0;
  border: 1px solid rgba(0, 0, 0, 0.12);
  padding: 0;
  box-sizing: border-box;
  max-height: 296px;
  overflow-y: auto;
  background: #fff;
  list-style: none;
  box-shadow: 0 2px 2px rgba(0, 0, 0, 0.16);
}

.modelarium-autocomplete__total-results {
  padding: 0px 12px;
  font-size: 0.8rem;
  font-style: italic;
}

.modelarium-autocomplete__result-item {
  cursor: default;
}
.modelarium-autocomplete__result-item:hover {
  background-color: #eee;
}

/* Loading spinner 
.autocomplete[data-loading="true"]::after {
    content: "";
    border: 3px solid rgba(0, 0, 0, 0.12);
    border-right: 3px solid rgba(0, 0, 0, 0.48);
    border-radius: 100%;
    width: 20px;
    height: 20px;
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    animation: rotate 1s infinite linear;
}

@keyframes rotate {
    from {
        transform: translateY(-50%) rotate(0deg);
    }
    to {
        transform: translateY(-50%) rotate(359deg);
    }
} */
</style>
